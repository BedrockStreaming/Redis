<?php

namespace M6Web\Component\Redis;

use Predis;

/**
 * Class PredisProxy
 */
class PredisProxy
{
    /** @var Predis\Client */
    protected $predis;

    /** @var int */
    protected $maxConnectionLostAllowed = 0;

    /**
     * @param Predis\Client $predis
     */
    public function __construct(Predis\Client $predis = null)
    {
        if (is_null($predis)) {
            $predis = new Predis\Client();
        }
        $this->predis = $predis;
    }

    /**
     * call Predis\Client constructor for init
     *
     * @param array $params
     * @param array $options
     */
    public function callRedisConstructor($params = [], $options = [])
    {
        $this->predis->__construct($params, $options);
    }

    /**
     * @param int $v
     *
     * @return $this
     */
    public function setMaxConnectionLostAllowed($v)
    {
        $this->maxConnectionLostAllowed = abs((int) $v);

        return $this;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @throws Predis\Connection\ConnectionException
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $errors = 0;
        $maxConnectionLostAllowed = $this->maxConnectionLostAllowed;

        if ('connect' === $name) {
            // dont reconnect on connect
            $maxConnectionLostAllowed = 0;
        }

        do {
            try {
                return call_user_func_array([$this->predis, $name], $arguments);
            } catch (Predis\Connection\ConnectionException $e) {
                // try to re-connect
                $lastException = $e;
                try {
                    $this->predis->connect();
                } catch (Predis\ClientException $e) {
                    // cant connect
                }
                $errors++;
            }
        } while ($maxConnectionLostAllowed >= $errors);

        throw $lastException;
    }
}
