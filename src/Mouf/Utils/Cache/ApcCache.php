<?php
namespace Mouf\Utils\Cache;

use Mouf\Validator\MoufValidatorInterface;
use Mouf\Validator\MoufValidatorResult;

/**
 * This package contains a cache mechanism that relies on APC.
 * 
 * @author David Negrier
 */
class ApcCache implements CacheInterface, MoufValidatorInterface {
	
	/**
	 * The default time to live of elements stored in the session (in seconds).
	 * Please note that if the session is flushed, all the elements of the cache will disapear anyway.
	 * If empty, the time to live will be the time of the session. 
	 *
	 * @var int
	 */
	public $defaultTimeToLive;
	
	/**
	 * A prefix to be added to all the keys of the cache. Very useful to avoid conflicting name between different instances.
	 * Note: the prefix is NOT added if the fallback service is used.
	 * 
	 * @var string
	 */
	public $prefix = "";
	
	/**
	 * The fallback cache service to use if APC is not available on the host.
	 * If no fallback mechanism is passed and APC is not available, an exception will be triggered.
	 * 
	 * @var CacheInterface
	 */
	public $fallback;
	
	/**
	 * The logger used to trace the cache activity.
	 *
	 * @var LogInterface
	 */
	public $log;
	
	/**
	 * If there is a condition interface, and if it returns false, APC cache will
	 * be disabled, and the fallback mechanism will be used instead.
	 * 
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
		$value = apc_fetch($this->prefix.$key, $success);
		
		if ($success) {
			if ($this->log) {
				$this->log->trace("Retrieving key '{$this->prefix}{$key}' from file cache.");
			}
			return $value;
		} else {
			if ($this->log) {
				$this->log->trace("Retrieving key '{$this->prefix}{$key}' from file cache: cache miss");
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
			$this->log->trace("Storing value in APC cache: key '{$this->prefix}{$key}'.");
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
		
		$ret = apc_store($this->prefix.$key, $value, $timeOut);
		if ($ret == false) {
			if ($this->log) {
				$this->log->error("Error while caching the key '{$this->prefix}{$key}' with value '".var_export($value, true)."' in APC cache.");
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
			$this->fallback->purge($key);
			return;
		}
		
		if ($this->log) {
			$this->log->trace("Purging key '{$this->prefix}{$key}' from APC cache.");
		}
		
		apc_delete($this->prefix.$key);
	}
	
	/**
	 * Removes all the objects from the cache.
	 *
	 */
	public function purgeAll() {
		$this->init();
		if ($this->useFallback) {
			$this->fallback->purgeAll();
			return;
		}		
		
		if (empty($this->prefix)) {
			apc_clear_cache('user');
		} else {
			$info = apc_cache_info("user");
			foreach ($info['cache_list'] as $obj) {
				if (strpos($obj['info'], $this->prefix) === 0) {
					apc_delete($obj['info']);
				}
			}
		}
	}
	
	/**
	 * Runs the validation of the class.
	 * Returns a MoufValidatorResult explaining the result.
	 *
	 * @return MoufValidatorResult
	 */
	public function validateInstance() {		
		if (extension_loaded("apc")) {
			return new MoufValidatorResult(MoufValidatorResult::SUCCESS, "APC extension found");
		} else {
			if ($this->fallback) {
				return new MoufValidatorResult(MoufValidatorResult::WARN, "APC extension is not installed. The APCCache service will use the configured fallback method instead.");
			} else {
				return new MoufValidatorResult(MoufValidatorResult::ERROR, "APC extension is not installed. No fallback method configured.");
			}
		}
	}
}
?>