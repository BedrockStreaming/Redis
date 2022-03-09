<?php

namespace M6Web\Component\Redis\tests\units;

use M6Web\Component\Redis;

class Multi extends \atoum
{
    public const spacename = 'testCacheMulti';

    private function getServerConfig($config)
    {
        if ($config == 'many') {
            return [
                'php51' => [
                    'ip' => '127.0.0.1',
                    'port' => 6379,
                ],
                'php52' => [
                'ip' => '127.0.0.1',
                'port' => 6379,
                ],
            ];
        }
        if ($config == 'manywrong') {
            return [
                'php51' => [
                    'ip' => '127.0.0.1',
                    'port' => 6379,
                ],
                'php52' => [
                    'ip' => '1.0.0.1',
                    'port' => 6379,
                ],
            ];
        }
        if ($config == 'wrong') {
            return [
                'phpraoul' => [  // mauvais server
                    'ip' => '1.2.3.4',
                    'port' => 6379,
                ],
            ];
        }
        if ($config == 'unavailable') {
            return [
                'phpraoul' => [  // mauvais server
                    'ip' => '1.2.3.4',
                    'port' => 6379,
                ],
                'phpraoul2' => [  // mauvais server
                    'ip' => '1.2.3.5',
                    'port' => 6379,
                ],
            ];
        }
        throw new \Exception('one or wrong can be accessed via '.__METHOD__.' not : '.$config);
    }

    public function testWorking()
    {
        $server_config = $this->getServerConfig('many');

        $this->assert
            ->if($redis = new redis\Multi([
                'timeout' => 0.1,
                'server_config' => $server_config,
            ]))
            ->then($redis->onAllServer()->set(self::spacename.'foo', 'bar'))
                ->string($redis->onOneRandomServer()->get(self::spacename.'foo'))
                    ->isEqualTo('bar');
    }

    public function testNoRandomServerAvailable()
    {
        $server_config = $this->getServerConfig('unavailable');

        $this->assert
            ->if($redis = new redis\Multi([
                'timeout' => 0.1,
                'server_config' => $server_config,
            ]))
            ->then
                ->exception(
                    function () use ($redis) {
                        $redis->onOneRandomServer()->get(self::spacename.'foo');
                    }
                )
                ->isInstanceOf('\M6Web\Component\Redis\Exception')
                    ->hasMessage("Can't connect to a random redis server");
    }

    public function testOneServerWorking()
    {
        $server_config = $this->getServerConfig('many');

        $this->assert
            ->if($redis = new redis\Multi([
                'timeout' => 0.1,
                'server_config' => $server_config,
            ]))
            ->then($redis->onOneServer('php51')->set(self::spacename.'foo', 'bar'))
                ->string($redis->onOneServer('php51')->get(self::spacename.'foo'))
                    ->isEqualTo('bar');
    }

    public function testNoOneServerAvailable()
    {
        $server_config = $this->getServerConfig('unavailable');

        $this->assert
            ->if($redis = new redis\Multi([
                'timeout' => 0.1,
                'server_config' => $server_config,
            ]))
            ->then
                ->exception(
                    function () use ($redis) {
                        $redis->onOneServer('php51')->get(self::spacename.'foo');
                    }
                )
                ->isInstanceOf('\M6Web\Component\Redis\Exception')
                    ->hasMessage('unknown redis php51');
    }

    public function testOneServerWrong()
    {
        $server_config = $this->getServerConfig('wrong');

        $this->assert
            ->if($redis = new redis\Multi([
                'timeout' => 0.1,
                'server_config' => $server_config,
            ]))
            ->then
                ->exception(
                    function () use ($redis) {
                        $redis->onOneServer('phpraoul')->get(self::spacename.'foo');
                    }
                )
                ->isInstanceOf('\M6Web\Component\Redis\Exception')
                    ->hasMessage('cant connect to redis phpraoul');
    }

    public function testOneKeyServerWorking()
    {
        $server_config = $this->getServerConfig('many');
        $key = self::spacename.'foo';
        $this->assert
            ->if($redis = new redis\Multi([
                'timeout' => 0.1,
                'server_config' => $server_config,
            ]))
            ->then($redis->onOneKeyServer($key)->set($key, 'bar'))
                ->string($redis->onOneKeyServer($key)->get($key))
                    ->isEqualTo('bar');
    }

    public function testNoKeyServerAvailable()
    {
        $server_config = $this->getServerConfig('unavailable');

        $this->assert
            ->if($redis = new redis\Multi([
                'timeout' => 0.1,
                'server_config' => $server_config,
            ]))
            ->then
            ->exception(
                function () use ($redis) {
                    $key = self::spacename.'foo';
                    $redis->onOneKeyServer($key)->get($key);
                }
            )
            ->isInstanceOf('\M6Web\Component\Redis\Exception')
            ->hasMessage('No redis server available ! ');
    }

    public function testManyWrong()
    {
        $server_config = $this->getServerConfig('manywrong');
        $this->assert
            ->if($redis = new redis\Multi([
                'timeout' => 0.1,
                'server_config' => $server_config,
            ]))
            ->then()
            ->exception(
                function () use ($redis) {
                    $redis->onAllServer()->ping();
                }
            )
            ->isInstanceOf('\M6Web\Component\Redis\Exception')
            ->array($redis->onAllServer($strict = false)->ping())
                ->contains(true)
                ->size
                    ->isEqualTo(1);
    }

    public function testBadUsage()
    {
        $server_config = $this->getServerConfig('many');
        $this->assert
            ->if($redis = new redis\Multi([
                    'timeout' => 0.1,
                    'server_config' => $server_config,
                ]))
            ->then()
            ->exception(
                function () use ($redis) {
                    $redis->ping();
                }
            )
            ->isInstanceOf('\M6Web\Component\Redis\Exception');
    }

    public function tearDown()
    {
        $server_config = $this->getServerConfig('many');
        $r = new redis\Multi([
            'timeout' => 0.1,
            'server_config' => $server_config,
        ]);
        foreach ($r->getServerConfig() as $server_id => $server) {
            if ($redis = $r->getRedisFromServerConfig($server_id)) {
                $all_keys = $redis->keys(self::spacename.'*'); // toutes les clés commençant par le pattern
                if (count($all_keys)) {
                    $redis->del($all_keys);
                }
            }
        }
    }
}
