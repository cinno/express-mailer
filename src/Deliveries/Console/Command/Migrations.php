<?php
namespace Deliveries\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Deliveries\Aware\Console\Command\BaseCommandAware;

/**
 * Migrations class. Application Migrations command
 *
 * @package Deliveries
 * @subpackage Deliveries\Console\Command
 * @since PHP >=5.5
 * @version 1.0
 * @author Stanislav WEB | Lugansk <stanisov@gmail.com>
 * @copyright Stanislav WEB
 * @filesource /Deliveries/Console/Command/Migrations.php
 */
class Migrations extends BaseCommandAware {

    /**
     * Command logo
     *
     * @const NAME
     */
    const LOGO = "###################\nMigration Tools ###\n###################";
    /**
     * Command name
     *
     * @const NAME
     */
    const NAME = 'migrations';

    /**
     * Command description
     *
     * @const DESCRIPTION
     */
    const DESCRIPTION = 'Storage migrations tool';

    /**
     * Storage migration path
     *
     * @const MIGRATION_DB
     */
    const MIGRATION_DB = '/build/migrations/';

    /**
     * Default storage prefix
     *
     * @const DEFAULT_PREFIX
     */
    const DEFAULT_PREFIX = '';

    /**
     * @const SUCCESS command success
     */
    const SUCCESS = 'Data imported successfully to %s';

    /**
     * Prompt string formatter
     *
     * @var array $prompt
     */
    private $prompt = [
        'STORAGE_TABLE_PREFIX_TYPE' =>  "<info>Please type import %s tables prefix (default `%s`):</info> ",
        'STORAGE_IMPORT_SUCCESS'    =>  "<info>Data imported successfully to %s</info>",
        'STORAGE_DATA_WARNING'      =>  "<comment>Some of the data is already imported into your base.</comment>\n",
        'STORAGE_DATA_OVERWRITE'    =>  "<question>Do you want to continue? This action will overwrite the already previously imported table?:</question> [<comment>no/yes</comment>] ",
    ];

    /**
     * Migration files path
     *
     * @var string $migrationFilesPath
     */
    private $migrationFilesPath;

    /**
     * Migration files array
     *
     * @var array $migrationFiles
     */
    private $migrationFiles = [];

    /**
     * Get Storage configurations
     *
     * @return array
     */
    public function getConfig() {
        return parent::getConfig()->Storage;
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

        // checking connection
        if($this->isStorageConnectSuccess($this->getConfig())) {

            $prefix = $this->getPrompt(sprintf($this->prompt['STORAGE_TABLE_PREFIX_TYPE'], $this->getConfig()['adapter'], self::DEFAULT_PREFIX), $input, $output,
                function($answer) {

                    if(empty($answer) === true) {
                        return self::DEFAULT_PREFIX;
                    }
                    return $answer;
            });

            if(empty($this->checkImportTables()) === false) {

                // ask for rewrite existing tables
                if($this->cautionDialog($input, $output) === false) {
                    return ;
                }
            }

            // add to config
            $this->addToConfig(null, ['Storage' => ['prefix' => $prefix]]);
            $this->import($prefix);

            $message = sprintf($this->prompt['STORAGE_IMPORT_SUCCESS'], $this->getConfig()['adapter']);
            $this->logger()->info($message);
            $output->writeln($message);

            return null;
        }

    }

    /**
     * Check tables if this already imported
     *
     * @return array
     */
    private function checkImportTables() {

        $this->migrationFilesPath = getcwd().self::MIGRATION_DB.strtolower($this->getConfig()['adapter']).'/';

        // get db tables list
        $dbTables = $this->getStorageInstance()->getTablesList();

        // get reserved db data from migration
        $files = [];
        foreach (new \DirectoryIterator($this->migrationFilesPath) as $file) {
            if($file->isDot()) continue;
            $this->migrationFiles[] = pathinfo($file->getFilename(), PATHINFO_BASENAME);
            $files[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        }

        // return tables difference
        return array_intersect($files, $dbTables);
    }

    /**
     * Rewrite caution dialog
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @throws \InvalidArgumentException
     * @return bool
     */
    private function cautionDialog(InputInterface $input, OutputInterface $output) {

        $helper = $this->getHelper('question');

        $question = new Question([
            sprintf($this->prompt['STORAGE_DATA_WARNING']),
            sprintf($this->prompt['STORAGE_DATA_OVERWRITE'])
        ]);

        $question->setValidator(function($typeInput) {
            if (!in_array($typeInput, ['no', 'yes'])) {
                throw new \InvalidArgumentException('Invalid input type. Please [yes] or [no]');
            }
            return $typeInput;
        });

        $isRewrite = $helper->ask($input, $output, $question);

        return ($isRewrite == 'yes') ? true : false;
    }

    /**
     * Import tables to database
     *
     * @param string $prefix
     * @throws \RuntimeException
     */
    private function import($prefix) {

        if(empty($this->migrationFiles) === false) {

            $sm = $this->getAppServiceManager();

            asort($this->migrationFiles);

            foreach($this->migrationFiles as $file) {

                $commands = $this->parseFileToSingleQueries($prefix, $file);

                foreach($commands as $query) {

                    // Import query
                    $sm->importTables($query);

                }
            }
            return;
        }
        throw new \RuntimeException(
            'Migrations files are not allowed'
        );
    }

    /**
     * Parse file by line for single queries
     *
     * @param string $prefix
     * @param string $file
     * @throws \RuntimeException
     * @return array
     */
    private function parseFileToSingleQueries($prefix, $file) {

        if(file_exists($this->migrationFilesPath.$file) === true) {
            $commands = [];
            $queries = str_replace('__PREFIX__', $prefix, file_get_contents($this->migrationFilesPath.$file));
            foreach(explode(";", $queries) as $command) {
                if(empty(trim($command)) === false) {
                    $commands[] = trim($command);
                }
            }

            return $commands;
        }
        throw new \RuntimeException(
            'Db file : `'.$file.'` is not exist'
        );
    }
}