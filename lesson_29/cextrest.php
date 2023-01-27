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
		if (static::$currentBitrix24 != '')
			return  (boolean)file_put_contents(static::getSettingsDir().static::$currentBitrix24.'.json', static::wrapData($arSettings));
		else return false;
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
}