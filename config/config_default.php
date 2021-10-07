<?php
return array(
	'logs'=>array(
		'path'=>'backup/logs',
		'type'=>'file'
	),
	'DB'=>array(
		'type'=>'mysqli',
        'tablePre'=>'{TABLE_PREFIX}',
		'read'=>array(
			array('host'=>'{DB_R_ADDRESS}','user'=>'{DB_R_USER}','passwd'=>'{DB_R_PWD}','name'=>'{DB_R_NAME}'),
		),

		'write'=>array(
			'host'=>'{DB_W_ADDRESS}','user'=>'{DB_W_USER}','passwd'=>'{DB_W_PWD}','name'=>'{DB_W_NAME}',
		),
	),
	'interceptor' => array('themeroute@onCreateController','layoutroute@onCreateView','plugin'),
	'langPath' => 'language',
	'viewPath' => 'views',
	'skinPath' => 'skin',
    'classes' => 'classes.*',
    'rewriteRule' =>'url',
	'theme' => array('pc' => array('huawei' => 'default','sysdm' => 'default','sysiseller' => 'default'),'mobile' => array('mobile' => 'default','sysdm' => 'default','sysiseller' => 'default')),
	'timezone'	=> 'Etc/GMT-8',
	'upload' => 'upload',
	'dbbackup' => 'backup/database',
	'safe' => 'cookie',
	'lang' => 'zh_sc',
	'debug'=> '0',
	'configExt'=> array('site_config'=>'config/site_config.php','plugin_config'=>'config/plugin_config.php'),
	'encryptKey'=>'{ENCRYPTKEY}',
	'authorizeCode' => '',
	'uploadSize' => '10',
	'low_withdraw' => '1',
	'low_bill' => '0',
);
?>