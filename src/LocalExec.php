<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 04.09.20 03:33:42
 */

declare(strict_types = 1);
namespace dicr\exec;

use InvalidArgumentException;
use Yii;
use yii\base\Component;
use function array_filter;
use function fclose;
use function function_exists;
use function in_array;
use function is_resource;
use function ob_get_clean;
use function ob_start;
use function passthru;
use function pclose;
use function popen;
use function proc_close;
use function proc_open;
use function shell_exec;
use function stream_get_contents;
use function system;

/**
 * Выполнение команд локально.
 * Использует различные доступные методы.
 */
class LocalExec extends Component implements ExecInterface
{
    /**
     * Возвращает список запрещенных функций.
     *
     * @return string[]
     */
    public static function disabledFunctions() : array
    {
        /** @var array запрещенные функции */
        static $fns;

        if ($fns === null) {
            $disabledList = ini_get('disable_functions') . ' ' .
                ini_get('suhosin.executor.func.blacklist');

            $fns = preg_split('~[\s\,]+~um', $disabledList, - 1, PREG_SPLIT_NO_EMPTY);
        }

        return $fns;
    }

    /**
     * Проверяет запрещена ли функция.
     *
     * @param string $func название функции
     * @return bool
     */
    public static function isDisabled(string $func) : bool
    {
        return ! function_exists($func) || in_array($func, self::disabledFunctions());
    }

    /**
     * Создает команду для запуска.
     *
     * @param string $cmd команда
     * @param array $args аргументы
     * @param array $opts опции
     * - bool $escape экранировать аргументы
     * @return string
     */
    public static function createCommand(string $cmd, array $args = [], array $opts = []) : string
    {
        $cmd = trim($cmd);
        if ($cmd === '') {
            throw new InvalidArgumentException('empty cmd');
        }

        $command = escapeshellcmd($cmd);

        if (! empty($args)) {
            $args = array_filter($args, static function($val) : bool {
                return $val !== null;
            });

            if (! isset($opts['escape']) || $opts['escape']) {
                $args = array_map(static function($arg) : string {
                    return escapeshellarg($arg);
                }, $args);
            }

            $command .= ' ' . implode(' ', $args);
        }

        return $command;
    }

    /**
     * Выполняет exec
     *
     * @param string $cmd команда
     * @param array $args аргументы
     * @param array $opts опции
     *        - escape bool экранировать аргументы
     * @return string вывод команды
     * @throws ExecException
     */
    public static function exec(string $cmd, array $args = [], array $opts = []) : string
    {
        $cmd = self::createCommand($cmd, $args, $opts);

        $ret = 0;
        $out = [];

        Yii::debug('Запуск exec: ' . $cmd, __METHOD__);
        exec($cmd, $out, $ret);
        $out = implode('', $out);

        if (! empty($ret)) {
            throw new ExecException($cmd, $out, $ret);
        }

        return $out;
    }

    /**
     * Выполняет passthru
     *
     * @param string $cmd команда
     * @param array $args аргументы
     * @param array $opts опции
     * - bool $escape экранировать аргументы
     * @return string вывод команды
     * @throws ExecException
     */
    public static function passthru(string $cmd, array $args = [], array $opts = []) : string
    {
        $cmd = self::createCommand($cmd, $args, $opts);

        $ret = 0;

        Yii::debug('Запуск passthru: ' . $cmd, __METHOD__);

        ob_start();
        passthru($cmd, $ret);
        $out = ob_get_clean();

        if (! empty($ret)) {
            throw new ExecException($cmd, $out, $ret);
        }

        return $out;
    }

    /**
     * Выполняет shell_exec
     *
     * @param string $cmd команда
     * @param array $args аргументы
     * @param array $opts опции
     * - bool $escape экранировать аргументы
     * @return string вывод команды
     * @throws ExecException
     */
    public static function shellExec(string $cmd, array $args = [], array $opts = []) : string
    {
        $cmd = self::createCommand($cmd, $args, $opts);
        Yii::debug('Запуск shell_exec: ' . $cmd, __METHOD__);

        $out = shell_exec($cmd);
        if ($out === null) {
            throw new ExecException($cmd);
        }

        return $out;
    }

    /**
     * Выполняет proc_open
     *
     * @param string $cmd команда
     * @param array $args аргументы
     * @param array $opts опции
     * - bool $escape экранировать аргументы
     * @return string вывод команды
     * @throws ExecException
     */
    public static function popen(string $cmd, array $args = [], array $opts = []) : string
    {
        $cmd = self::createCommand($cmd, $args, $opts);
        Yii::debug('Запуск popen: ' . $cmd, __METHOD__);

        $f = popen($cmd, 'r');
        if (! $f) {
            throw new ExecException($cmd);
        }

        try {
            $out = stream_get_contents($f);
            if ($out === false) {
                throw new ExecException($cmd);
            }
        } finally {
            if (pclose($f) === - 1) {
                throw new ExecException($cmd);
            }
        }

        return $out;
    }

    /**
     * Выполняет proc_open.
     *
     * @param string $cmd команда
     * @param array $args аргументы
     * @param array $opts опции
     * - bool $escape экранировать аргументы
     * @return string вывод команды
     * @throws ExecException
     */
    public static function procOpen(string $cmd, array $args = [], array $opts = []) : string
    {
        $cmd = self::createCommand($cmd, $args, $opts);
        Yii::debug('Запуск proc_open: ' . $cmd, __METHOD__);

        $pipes = [];

        $proc = proc_open($cmd, [
            //0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes);

        if (! is_resource($proc)) {
            throw new ExecException($cmd);
        }

        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $err = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $ret = proc_close($proc);
        if (! empty($ret)) {
            throw new ExecException($cmd, $err, $ret);
        }

        return $out;
    }

    /**
     * Выполняет system.
     * ВНИМАНИЕ! Возвращает только последнюю строку вывода!
     *
     * @param string $cmd команда
     * @param array $args аргументы
     * @param array $opts опции
     * - bool $escape экранировать аргументы
     * @return string последняя строка вывода команды
     * @throws ExecException
     */
    public static function system(string $cmd, array $args = [], array $opts = []) : string
    {
        $cmd = self::createCommand($cmd, $args, $opts);
        $ret = 0;

        Yii::debug('Запуск system: ' . $cmd, __METHOD__);

        ob_start();
        system($cmd, $ret);
        $out = ob_get_clean();

        if (! empty($ret)) {
            throw new ExecException($cmd, $out, $ret);
        }

        return $out;
    }

    /**
     * @inheritdoc
     */
    public function run(string $cmd, array $args = [], array $opts = []) : string
    {
        $out = null;

        if (! self::isDisabled('exec')) {
            $out = self::exec($cmd, $args, $opts);
        } elseif (! self::isDisabled('shell_exec')) {
            $out = self::shellExec($cmd, $args, $opts);
        } elseif (! self::isDisabled('passthru')) {
            $out = self::passthru($cmd, $args, $opts);
        } elseif (! self::isDisabled('popen')) {
            $out = self::popen($cmd, $args, $opts);
        } elseif (! self::isDisabled('proc_open')) {
            $out = self::procOpen($cmd, $args, $opts);
        } elseif (! self::isDisabled('system')) {
            $out = self::system($cmd, $args, $opts);
        } /** @noinspection InvertedIfElseConstructsInspection */ else {
            throw new ExecException(self::createCommand($cmd, $args, $opts), 'Все функции запрещены');
        }

        return $out;
    }
}
