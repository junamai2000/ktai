<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 filetype=php: */
class CDateLogRoute extends CFileLogRoute
{
	public function getLogFile()
	{
	  return parent::getLogFile() . strftime("%Y%m%d", $_SERVER['REQUEST_TIME']);
	}

  public function getLogPath()
  {
    return Yii::app()->getRuntimePath() . strftime("/%Y/%m", $_SERVER['REQUEST_TIME']);
  }

	protected function processLogs($logs)
	{
		$logFile=$this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();
		foreach($logs as $log)
			error_log($this->formatLogMessage($log[0],$log[1],$log[2],$log[3]),3,$logFile);
	}
}
