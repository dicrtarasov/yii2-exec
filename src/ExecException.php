<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license MIT
 * @version 04.09.20 02:40:51
 */

declare(strict_types = 1);
namespace dicr\exec;

use Throwable;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

/**
 * Ошибка выполнения команды.
 */
class ExecException extends Exception
{
    /** @var string выполняемая команда */
    protected $cmd;

    /**
     * {@inheritDoc}
     *
     * @param string $cmd команда
     * @param ?string $error ошибка (если null, то берет error_get_last)
     * @param int $code код ошибки
     * @param ?Throwable $prev предыдущая проблема
     */
    public function __construct(string $cmd, ?string $error = null, int $code = 0, ?Throwable $prev = null)
    {
        if ($cmd === '') {
            throw new InvalidArgumentException('cmd');
        }

        $this->cmd = $cmd;

        if ($error === null) {
            $last = error_get_last();
            if (! empty($last['message'])) {
                $error = $last['message'];
                error_clear_last();
            } else {
                $error = 'Ошибка запуска команды';
            }
        }

        parent::__construct($error, $code, $prev);
    }

    /**
     * Возвращает команду
     *
     * @return string
     */
    public function getCmd() : string
    {
        return $this->cmd;
    }
}
