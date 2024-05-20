app_name=FullTextSearch

build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(shell echo $(app_name) | tr '[:upper:]' '[:lower:]')
cert_dir=$(HOME)/.nextcloud/certificates
github_account=nextcloud
release_account=nextcloud-releases
branch=stable29
version=29.0.0
since_tag=

all: appstore

release: appstore github-release github-upload

github-release:
	if [ -z "$(release_account)" ]; then \
		release_account=$(github_account); \
		release_branch=$(branch); \
	else \
		release_account=$(release_account); \
		release_branch=master; \
	fi; \
	if [ -z "$(since_tag)" ]; then \
		latest_tag=$$(git describe --tags `git rev-list --tags --max-count=1`); \
	else \
		latest_tag=$(since_tag); \
	fi; \
	comparison="$$latest_tag..HEAD"; \
	if [ -z "$$latest_tag" ]; then comparison=""; fi; \
	changelog=$$(git log $$comparison --oneline --no-merges | sed -e 's/^/$(github_account)\/$(package_name)@/'); \
	github-release release \
		--user $$release_account \
		--repo $(package_name) \
		--target $$release_branch \
		--tag $(version) \
		--description "**Changelog**<br/>$$changelog" \
		--name "$(app_name) v$(version)"; \
	if [ $(github_account) != $$release_account ]; then \
	        link="https://github.com/$$release_account/$(package_name)/releases/download/$(version)/$(package_name)-$(version).tar.gz";\
		github-release release \
			--user $(github_account) \
			--repo $(package_name) \
			--target $(branch) \
			--tag $(version) \
			--description "**Download**<br />$$link<br /><br />**Changelog**<br/>$$changelog<br />" \
			--name "$(app_name) v$(version)"; \
	fi; \


github-upload:
	if [ -z "$(release_account)" ]; then \
		release_account=$(github_account); \
	else \
		release_account=$(release_account); \
	fi; \
	github-release upload \
		--user $$release_account \
		--repo $(package_name) \
		--tag $(version) \
		--name "$(package_name)-$(version).tar.gz" \
		--file $(build_dir)/$(package_name).tar.gz


clean:
	rm -rf $(build_dir)
	rm -rf node_modules

# composer packages
composer:
	composer install --prefer-dist
	composer upgrade --prefer-dist

appstore: clean composer
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=/build \
	--exclude=/docs \
	--exclude=/translationfiles \
	--exclude=/.tx \
	--exclude=/tests \
	--exclude=.git \
	--exclude=/.github \
	--exclude=/l10n/l10n.pl \
	--exclude=/CONTRIBUTING.md \
	--exclude=/issue_template.md \
	--exclude=/README.md \
	--exclude=/composer.json \
	--exclude=/testConfiguration.json \
	--exclude=/composer.lock \
	--exclude=/.gitattributes \
	--exclude=/.gitignore \
	--exclude=/.scrutinizer.yml \
	--exclude=/.travis.yml \
	--exclude=/Makefile \
	./ $(sign_dir)/$(package_name)
	tar -czf $(build_dir)/$(package_name).tar.gz \
		-C $(sign_dir) $(package_name)
	@if [ -f $(cert_dir)/$(package_name).key ]; then \
		echo "Signing package…"; \
		openssl dgst -sha512 -sign $(cert_dir)/$(package_name).key $(build_dir)/$(package_name).tar.gz | openssl base64; \
	fi
