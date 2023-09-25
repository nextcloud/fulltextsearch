<?php
declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\FullTextSearch\Command;


use Exception;
use OC\Core\Command\InterruptedException;
use OCA\FullTextSearch\ACommandBase;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\RunningService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;


/**
 * Class Reset
 *
 * @package OCA\FullTextSearch\Command
 */
class Reset extends ACommandBase {

	private Runner $runner;

	public function __construct(
		RunningService $runningService,
		private IndexService $indexService
	) {
		parent::__construct();

		$this->runner = new Runner($runningService, 'commandReset');
	}

	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:reset')
			->setDescription('Reset index')
			->addOption('provider', '', InputOption::VALUE_REQUIRED, 'provider id', '')
			->addOption('collection', '', InputOption::VALUE_REQUIRED, 'name of the collection', '');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$provider = $input->getOption('provider');
		$collection = $input->getOption('collection');
		$helper = $this->getHelper('question');

		$output->writeln('<error>WARNING! You are about to reset your indexed documents:</error>');
		$output->writeln('- provider: <info>' . (($provider === '') ? 'ALL' : $provider) . '</info>');
		$output->writeln('- collection: <info>' . (($collection === '') ? 'ALL' : $collection) . '</info>');
		$output->writeln('');

		$question = new ConfirmationQuestion(
			'<comment>Do you really want to reset your indexed documents ?</comment> (y/N) ', false,
			'/^(y|Y)/i'
		);

		if (!$helper->ask($input, $output, $question)) {
			$output->writeln('');
			$output->writeln('aborted.');

			return 0;
		}

		$output->writeln('');
		$output->writeln('<error>WARNING! This operation is not reversible.</error>');
		$action = 'reset ' . (($provider === '') ? 'ALL' : $provider)
			. ' ' . (($collection === '') ? 'ALL' : $collection);

		$question = new Question('<comment>Please confirm this destructive operation by typing \'' . $action . '\'</comment>: ', '');

		$helper = $this->getHelper('question');
		$confirmation = $helper->ask($input, $output, $question);
		if (strtolower($confirmation) !== strtolower($action)) {
			$output->writeln('');
			$output->writeln('aborted.');

			return 0;
		}

		try {
			$this->runner->sourceIsCommandLine($this, $output);
			$this->runner->start();
		} catch (Exception $e) {
			$this->runner->exception($e->getMessage(), true);
			throw $e;
		}

		$this->indexService->setRunner($this->runner);
		try {
			$this->indexService->resetIndex($provider, $collection);
			$output->writeln('');
			$output->writeln('done.');

		} catch (Exception $e) {
			throw $e;
		} finally {
			$this->runner->stop();
		}

		return 0;
	}


	/**
	 * @throws TickDoesNotExistException
	 */
	public function abort() {
		try {
			$this->abortIfInterrupted();
		} catch (InterruptedException $e) {
			$this->runner->stop();
			exit();
		}
	}
}



