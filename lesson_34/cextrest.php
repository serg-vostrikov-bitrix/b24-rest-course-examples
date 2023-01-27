<?php

include_once(__DIR__.'/crest.php');

class CRestExt extends CRest
{
	protected static $currentBitrix24 = [];

	static function setCurrentBitrix24($memberId)
	{
		static::$currentBitrix24 = $memberId;
	}

	static function getCurrentBitrix24()
	{
		return static::$currentBitrix24;
	}

	protected static function getSettingsDir()
	{

		$dir = __DIR__ . '/settings/';

		if(!file_exists($dir))
		{
			mkdir($dir, 0775, true);
		}

		return $dir;
	}

	protected static function getSettingData()
	{
		if (static::$currentBitrix24 != '') {
			$return = static::expandData(file_get_contents(static::getSettingsDir().static::$currentBitrix24.'.json'));
		}

		return $return;
	}

	protected static function setSettingData($arSettings)
	{
        $result = false;
		if (static::$currentBitrix24 != '') {
			$result = (boolean)file_put_contents(static::getSettingsDir().static::$currentBitrix24.'.json', static::wrapData($arSettings));
			@chmod(static::getSettingsDir().static::$currentBitrix24.'.json', 0664);
        }
		
		return $result;
	}

	public static function installApp()
	{
		if($_REQUEST[ 'event' ] == 'ONAPPINSTALL' && !empty($_REQUEST[ 'auth' ]))
		{
			static::setCurrentBitrix24($_REQUEST[ 'auth' ]['member_id']);
		}
		elseif($_REQUEST['PLACEMENT'] == 'DEFAULT')
		{
			static::setCurrentBitrix24($_REQUEST['member_id']);
		}

		return parent::installApp();
	}
	
	/**
	 * Can overridden this method to change the log data storage location.
	 *
	 * @var $arData array of logs data
	 * @var $type   string to more identification log data
	 * @return boolean is successes save log data
	 */

	public static function setLog($arData, $type = '')
	{
		$return = false;
		if(!defined("C_REST_BLOCK_LOG") || C_REST_BLOCK_LOG !== true)
		{
			if(defined("C_REST_LOGS_DIR"))
			{
				$path = C_REST_LOGS_DIR;
			}
			else
			{
				$path = __DIR__ . '/logs/';
			}
			if (!file_exists($path))
			{
				@mkdir($path, 0775, true);
				@chmod($path, 0775);
			}
			
			$path .= date("Y-m-d") . '/';

			if (!file_exists($path))
			{
				@mkdir($path, 0775);
				@chmod($path, 0775);
			}
			$path .= date("H") . '/';
			if (!file_exists($path))
			{
				@mkdir($path, 0775);
				@chmod($path, 0775);
			}

			$path .= time() . '_' . $type . '_' . rand(1, 9999999) . 'log';
			if(!defined("C_REST_LOG_TYPE_DUMP") || C_REST_LOG_TYPE_DUMP !== true)
			{
				$jsonLog = static::wrapData($arData);
				if ($jsonLog === false)
				{
					$return = file_put_contents($path . '_backup.txt', var_export($arData, true));
					@chmod($path . '_backup.txt', 0664);
				}
				else
				{
					$return = file_put_contents($path . '.json', $jsonLog);
					@chmod($path . '.json', 0664);
				}
			}
			else
			{
				$return = file_put_contents($path . '.txt', var_export($arData, true));
				@chmod($path . '.txt', 0664);
			}
		}
		return $return;
	}
}