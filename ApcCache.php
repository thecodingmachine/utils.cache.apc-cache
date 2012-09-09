<?php

/**
 * This package contains a cache mechanism that relies on APC.
 * 
 * @Component
 */
class ApcCache implements CacheInterface {
	
	/**
	 * The default time to live of elements stored in the session (in seconds).
	 * Please note that if the session is flushed, all the elements of the cache will disapear anyway.
	 * If empty, the time to live will be the time of the session. 
	 *
	 * @Property
	 * @var int
	 */
	public $defaultTimeToLive;
	
	/**
	 * The fallback cache service to use if APC is not available on the host.
	 * If no fallback mechanism is passed and APC is not available, an exception will be triggered.
	 * 
	 * @Property
	 * @var CacheInterface
	 */
	public $fallback;
	
	/**
	 * The logger used to trace the cache activity.
	 *
	 * @Property
	 * @var LogInterface
	 */
	public $log;
	
	/**
	 * If there is a condition interface, and if it returns false, APC cache will
	 * be disabled, and the fallback mechanism will be used instead.
	 * 
	 * @Property
	 * @var ConditionInterface
	 */
	public $condition;
	
	private $useFallback = false;
	
	private $initDone = false;
	
	public function init() {
		if ($this->initDone) {
			return;
		}
		
		if (!extension_loaded("apc")) {
			if ($this->fallback) {
				if ($this->log) {
					$this->log->info("APC extension not available. Using fallback.");
				}
				$this->useFallback = true;
			} else {
				throw new ApcCacheException("APC is not available and no fallback mechanism has been passed.");
			}
		}
		
		if ($this->condition && !$this->condition->isOk()) {
			if ($this->fallback == null) {
				$this->fallback = new NoCache();
			}
			$this->useFallback = true;
		}
	}
	
	/**
	 * Returns the cached value for the key passed in parameter.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		$this->init();
		if ($this->useFallback) {
			return $this->fallback->get($key); 
		}
		
		$success = false;
		$value = apc_fetch($key, $success);
		
		if ($success) {
			if ($this->log) {
				$this->log->trace("Retrieving key '$key' from file cache.");
			}
			return $value;
		} else {
			if ($this->log) {
				$this->log->trace("Retrieving key '$key' from file cache: cache miss");
			}
			return null;
		}
	}
	
	/**
	 * Sets the value in the cache.
	 *
	 * @param string $key The key of the value to store
	 * @param mixed $value The value to store
	 * @param float $timeToLive The time to live of the cache, in seconds.
	 */
	public function set($key, $value, $timeToLive = null) {
		$this->init();
		if ($this->useFallback) {
			return $this->fallback->set($key, $value, $timeToLive);
		}
		
		if ($this->log) {
			$this->log->trace("Storing value in APC cache: key '$key'.");
		}
		
		if ($timeToLive == null) {
			if (empty($this->defaultTimeToLive)) {
				$timeOut = 0;
			} else {
				$timeOut = $this->defaultTimeToLive;
			}
		} else {
			$timeOut = time() + $timeToLive;
		}
		
		$ret = apc_store($key, $value, $timeOut);
		if ($ret == false) {
			if ($this->log) {
				$this->log->error("Error while caching the key '$key' with value '".var_export($value, true)."' in APC cache.");
			}
		}
	}
	
	/**
	 * Removes the object whose key is $key from the cache.
	 *
	 * @param string $key The key of the object
	 */
	public function purge($key) {
		$this->init();
		if ($this->useFallback) {
			return $this->fallback->purge($key);
		}
		
		if ($this->log) {
			$this->log->trace("Purging key '$key' from APC cache.");
		}
		
		apc_delete($key);
	}
	
	/**
	 * Removes all the objects from the cache.
	 *
	 */
	public function purgeAll() {
		apc_clear_cache('user');
	}
	
}
?>