<?php
namespace Deliveries\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ko\ProcessManager;
use Ko\Process as ForkProcess;
use Deliveries\Exceptions\AppException;
use Deliveries\Aware\Console\Command\BaseCommandAware;
use Deliveries\Service\AppServiceManager;
use Deliveries\Aware\Helpers\ProgressTrait;
use Deliveries\Aware\Helpers\FormatTrait;

error_reporting(0);

/**
 * Check class. Application checking command
 *
 * @package Deliveries
 * @subpackage Deliveries\Console\Command
 * @since PHP >=5.5
 * @version 1.0
 * @author Stanislav WEB | Lugansk <stanisov@gmail.com>
 * @copyright Stanislav WEB
 * @filesource /Deliveries/Console/Command/Check.php
 */
class Check extends BaseCommandAware {

    /**
     * Command logo
     *
     * @const NAME
     */
    const LOGO = "###################\nCheck tools #######\n###################";

    /**
     * Command name
     *
     * @const NAME
     */
    const NAME = 'check';

    const COMMAND = 'php bin/xmail thread --command=%s --arguments=%s';

    /**
     * Command description
     *
     * @const DESCRIPTION
     */
    const DESCRIPTION = 'Check tools. Validate subscriber\'s list & process';

    /**
     * Checking iterator
     *
     * @var int $i
     */
    private $i = 0;

    /**
     * Count of valid emails
     *
     * @var int $valid
     */
    private $valid = 0;

    /**
     * Count of invalid emails
     *
     * @var int $invalid
     */
    private $invalid = 0;

    /**
     * Prompt string formatter
     *
     * @var array $prompt
     */
    private $prompt = [
        'START_PROCESS'     =>  "Validate process for `%s` is started. Pid %d",
        'STATE_PROCESS'     => " \033[1;30mEmails check status:\033[1;30m \033[0;32m%s\033[0;32m / \033[5;31m%s\033[0;30m",
        'DONE_PROCESS'      =>  "Checking complete",
    ];

    use ProgressTrait, FormatTrait;

    /**
     * Get command additional options
     *
     * @return array
     */
    public static function getOptions()
    {

        return [
            new InputOption('queues', null, InputOption::VALUE_OPTIONAL, 'Queues'),
            new InputOption('subscribers', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_NONE, 'Subscribers'),
            new InputOption('autoremove', null, InputOption::VALUE_NONE, 'Remove invalid'),

        ];
    }

    /**
     * Execute command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @throws \RuntimeException
     * @return null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logo($output);

        // checking config
        if($this->isConfigExist() === false) {
            throw new \RuntimeException(
                'Configuration file does not exist! Run `xmail init`'
            );
        }

        // get requested data
        $request = $input->getOptions();
        $serviceManager = $this->getAppServiceManager();

        // check subscribers for valid
        if($request['subscribers'] !== false) {

            $this->subscribersVerify($output, $serviceManager, $request);
        }
    }

    /**
     * Verify subscribers email addresses
     *
     * @param OutputInterface                       $output
     * @param \Deliveries\Service\AppServiceManager $serviceManager
     * @param array                                 $request
     */
    private function subscribersVerify(OutputInterface $output, AppServiceManager $serviceManager, array $request) {

        // get subscribers
        $subscribers = $serviceManager->getUncheckedSubscribers($request['subscribers']);

        // count number of processes forks
        $forks = count($subscribers);

        $manager = new ProcessManager();

        // start process forks
        for ($f = 0; $f < $forks; $f++) {

            $manager->fork(function(ForkProcess $p) use ($subscribers, $output, $f, $serviceManager, $manager) {

                // create process title
                $this->createProcessTitle($output, $p, $manager);

                // create progress instance with total of subscribers
                $progress = $this->createProgress($output, $count = count($subscribers[$f]));

                while (++$this->i < $count) {

                    // verify subscriber email via SMTP
                    $subscriber = $serviceManager->verifyEmail($subscribers[$f][$this->i]['email'], true, true);
                    ($subscriber->isValid() === true) ? ++$this->valid : ++$this->invalid;

                    // update subscriber verify state
                    $serviceManager->setSubscriberState($subscribers[$f][$this->i]['subscriber_id'], $subscriber->isValid());

                    // print checkout process
                    $this->printProcess($progress);

                    if($this->i >= $count) {
                        $progress->finish();
                        break;
                    }
                }
            })->onSuccess(function() use ($output) {
                // process done
                $this->logOutput($output, $this->prompt['DONE_PROCESS'], ' <bg=white;options=bold>%s</>');

            })
            ->onError(function(ForkProcess $p) {
                throw new AppException('Error process: '.$p->getPid());
            })->wait();
        }
    }

    /**
     * Print process title
     *
     * @param OutputInterface $output
     * @param ForkProcess $p
     * @param ProcessManager $manager
     * @return null
     */
    private function createProcessTitle(OutputInterface $output, ForkProcess $p, $manager) {

        $processTitle = sprintf($this->prompt['START_PROCESS'], 'subscribers', $p->getPid());
        $manager->demonize();
        $p->setProcessTitle($processTitle);
        $this->logOutput($output, $processTitle, '<bg=white;options=bold>%s</>');

        return null;
    }

    /**
     * Print checkout live process
     *
     * @param \Symfony\Component\Console\Helper\ProgressBar $progress
     *
     * @return string
     */
    private function printProcess($progress) {
        return $progress->advance().' '.printf($this->prompt['STATE_PROCESS'], (int)$this->valid, (int)$this->invalid);
    }

    /**
     * Create progress
     *
     * @param OutputInterface $output
     * @param int           $subscribers
     * @return \Symfony\Component\Console\Helper\ProgressBar
     */
    private function createProgress(OutputInterface $output, $subscribers) {

        $progress = $this->getProgress($output, $subscribers, 'very_verbose');
        $progress->start();

        return $progress;
    }
}