<?php
/**
 * @author o_mansour
 */

namespace M6Web\Component\Redis;

/**
 * class for using Rédis as a DB
 */
class DB extends Manager
{
    /**
     * constructor - db is hardcoded
     *
     * @param array $params Manager parameters
     *
     * @throws Exception
     */
    public function __construct($params)
    {
        $maxNbServer = 1;
        $this->setCurrentDb(1); // default hardcoded choice for the db
        if (count($params['server_config']) > $maxNbServer) {
            $msg = 'cant declare more of '.$maxNbServer.' servers, '.count($params['server_config']).' found ';
            throw new Exception($msg);
        }
        if (isset($params['compress']) and ($params['compress'] === true)) {
            throw new Exception('cant use the compress option in this class');
        }
        if (isset($params['namespace'])) {
            throw new Exception('cant use the namespace option in this class');
        }

        return parent::__construct($params);
    }

    /**
     *  return a predis object
     *
     * @param int $serverRank
     *
     * @return false|object
     *
     * @throws Exception
     */
    public function getRedisObject($serverRank = 0)
    {
        $serverConfig = $this->getServerConfig();

        $keys = array_keys($serverConfig);
        if (isset($keys[$serverRank])) {
            return $this->getRedisFromServerConfig($keys[$serverRank]);
        } else {
            throw new Exception('No server found at rank '.$serverRank);
        }
    }

    /**
     *  magic method to the \Redis() proxy
     *
     * @param string $name      method name
     * @param array  $arguments method arguments
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if ($redis = $this->getRedisObject()) {
            $start = microtime(true);
            try {
                $ret = call_user_func_array([$redis, $name], $arguments);
                $this->notifyEvent($name, $arguments, microtime(true) - $start);

                return $ret;
            } catch (\Predis\PredisException $e) {
                throw new Exception('Error calling the method '.$name.' : '.$e->getMessage());
            }
        } else {
            throw new Exception('Cant connect to Redis');
        }
    }
}
