<?php

namespace M6Web\Component\Redis;

/**
 * class used for unit testing U
 */
class CacheTest extends Cache
{
    /**
     * nophpdoc
     *
     * @param int $key clé
     *
     * @return int
     */
    public function MyGetServerId($key)
    {
        return $this->getServerId($key);
    }

    public function getDeadRedis()
    {
        return $this->deadRedis;
    }
}
