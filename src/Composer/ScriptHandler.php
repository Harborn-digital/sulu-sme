<?php

namespace ConnectHolland\Sulu\SME\Composer;

use Composer\Script\Event;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * A handler for general tasks executed with composer scripts.
 */
class ScriptHandler
{

    /**
     * Handle post create project of composer
     *
     * @param Event $event
     */
    public static function postCreateProject(Event $event)
    {
        $io = $event->getIO();

        $name = $io->askAndValidate('What is the name of the project?: ', function ($answer) {
            if ($answer == '') {
                throw new \RuntimeException(
                    'The name of the project should not be empty'
                );
            }

            return $answer;
        }, 3);
        $url = $io->askAndValidate('What is the url for this project?: ', function ($answer) {
            if ($answer == '') {
                throw new \RuntimeException(
                    'The url of the project should not be empty'
                );
            }

            return $answer;
        }, 3);

        $createdKey = strtolower(
            preg_replace(
                '/[\/_|+ -]+/',
                '_',
                preg_replace(
                    '/[^a-zA-Z0-9\/_|+ -]/',
                    '',
                    $name
                )
            )
        );
        $key = $io->ask(sprintf('What is the key for this project? [%s]: ', $createdKey), $createdKey);

        $root = getcwd(); // defaults to corrent directory, or the --working-dir in composer call
        $template = file_get_contents(__DIR__.'/webspace.template');
        file_put_contents(sprintf('%s/app/Resources/webspaces/%s.xml', $root, $url), sprintf($template, $name, $key, $url));

        unlink(sprintf('%s/app/Resources/webspaces/.gitkeep', $root));

        $firstName = $io->ask('What is your first name?: ');
        $lastName = $io->ask('What is your last name?: ');
        $email = $io->ask('What is your emailaddress?: ');
        $password = $io->ask('What is your password you want to use?: ');

        $role = 'Admin';

        self::executeCommand($event, 'doctrine:migrations:migrate -n');
        self::executeCommand($event, 'sulu:build prod --env=dev -n');
        self::executeCommand($event, sprintf('sulu:security:role:create %s Sulu', $role));
        self::executeCommand($event, sprintf('sulu:security:user:create %s %s %s %s nl %s %s', strtolower($firstName), $firstName, $lastName, $email, $role, $password));
    }

    // NEXT lines are copy of sensio
    protected static function executeCommand(Event $event, $cmd, $timeout = 300)
    {
        $consoleDir = 'bin';
        $php = escapeshellarg(static::getPhp(false));
        $phpArgs = implode(' ', array_map('escapeshellarg', static::getPhpArguments()));
        $console = escapeshellarg($consoleDir.'/console');
        if ($event->getIO()->isDecorated()) {
            $console .= ' --ansi';
        }

        $process = new Process($php.($phpArgs ? ' '.$phpArgs : '').' '.$console.' '.$cmd, null, null, null, $timeout);
        $process->run(function ($type, $buffer) use ($event) { $event->getIO()->write($buffer, false); });
        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf("An error occurred when executing the \"%s\" command:\n\n%s\n\n%s.", escapeshellarg($cmd), $process->getOutput(), $process->getErrorOutput()));
        }
    }

    protected static function getPhp($includeArgs = true)
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find($includeArgs)) {
            throw new RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        return $phpPath;
    }

    protected static function getPhpArguments()
    {
        $ini = null;
        $arguments = array();

        $phpFinder = new PhpExecutableFinder();
        if (method_exists($phpFinder, 'findArguments')) {
            $arguments = $phpFinder->findArguments();
        }

        if ($env = strval(getenv('COMPOSER_ORIGINAL_INIS'))) {
            $paths = explode(PATH_SEPARATOR, $env);
            $ini = array_shift($paths);
        } else {
            $ini = php_ini_loaded_file();
        }

        if ($ini) {
            $arguments[] = '--php-ini='.$ini;
        }

        return $arguments;
    }
}
