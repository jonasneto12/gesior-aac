<?PHP
$config['site'] = parse_ini_file('config/config.ini');
session_start();
//save config in ini file
function saveconfig_ini($config) {
	$file = fopen("config/config.ini", "w");
	foreach($config as $param => $data) 
	{
$file_data .= $param.' = "'.str_replace('"', '', $data).'"
';
	}
	rewind($file);
	fwrite($file, $file_data);
	fclose($file);
}

function check_password($pass)
{
	$temp = strspn("$pass", "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890");
	if ($temp != strlen($pass)) 
	{
		return false;
	}
	else
	{
		$ok = "/[a-zA-Z0-9]{1,40}/";
		return (preg_match($ok, $pass))? true: false;
	}
}

function password_ency($password) 
{
	$ency = $GLOBALS['encryptionType'];
	if($ency == 'sha1') 
		return sha1($password);
	if($ency == 'md5') 
		return md5($password);
	if($ency == '') 
		return $password;
}

if($_REQUEST['page'] == '' && !isset($_REQUEST['step'])) 
{
	echo '<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2" />
			<title>Installation of account maker</title>
		</head>
		<frameset cols="230,*">
			<frame name="menu" src="install.php?page=menu" />
			<frame name="step" src="install.php?page=step&step=0" />
			<noframes><body>Frames don\'t work. Install Firefox :P</body></noframes>
		</frameset>
	</html>';
}
if($_REQUEST['page'] == 'menu') 
{
	echo '<h2>MENU</h2><br>
	<b>IF NOT INSTALLED:</b><br>
	<a href="install.php?page=step&step=start" target="step">0. Informations</a><br>
	<a href="install.php?page=step&step=1" target="step">1. Set server path</a><br>
	<a href="install.php?page=step&step=2" target="step">2. Check DataBase connection</a><br>
	<a href="install.php?page=step&step=3&server_conf=yes" target="step">3. Add tables and columns to DB</a><br>
	<a href="install.php?page=step&step=4&server_conf=yes" target="step">4. Add samples to DB</a><br>
	<a href="install.php?page=step&step=5&server_conf=yes" target="step">5. Set Admin Account</a><br>
	<b>FOR ADMINS:</b><br>
	<a href="index.php?subtopic=adminpanel&action=install_monsters" target="step">6. Load Monsters from OTS</a><br>
	<a href="index.php?subtopic=adminpanel&action=install_spells" target="step">7. Load Spells from OTS</a><br>';
}
if($_REQUEST['page'] == 'step') 
{
	if($config['site']['install'] != "no") 
	{
		if($_REQUEST['server_conf'] == 'yes' || ($_REQUEST['step'] > 2 && $_REQUEST['step'] < 6)) 
		{
			//load server config
			$config['server'] = parse_ini_file($config['site']['server_path'].'config.lua');
			if(isset($config['server']['sqlHost'])) 
			{
				$mysqlhost = $config['server']['sqlHost'];
				$mysqluser = $config['server']['sqlUser'];
				$mysqlpass = $config['server']['sqlPass'];
				$mysqldatabase = $config['server']['sqlDatabase'];
			}
			$sqlitefile = $config['server']['sqliteDatabase'];
			if(strtolower($config['server']['encryptionType']) == 'md5')
				$encryptionType = 'md5';
			if(strtolower($config['server']['encryptionType']) == 'sha1')
				$encryptionType = 'sha1';
			// loads #####POT mainfile#####
			include('pot/OTS.php');
			// PDO and POT connects to database
			$ots = POT::getInstance();
			if(strtolower($config['server']['sqlType']) == "mysql")
			{
				//connect to MySQL database
				try
				{
					$ots->connect(POT::DB_MYSQL, array('host' => $mysqlhost, 'user' => $mysqluser, 'password' => $mysqlpass, 'database' => $mysqldatabase) );
				}
				catch(PDOException $error)
				{
					echo 'Database error - can\'t connect to MySQL database. Possible reasons:<br>1. MySQL server is not running on host.<br>2. MySQL user, password, database or host isn\'t configured in: <b>'.$config['site']['server_path'].'config.lua</b>.<br>3. MySQL user, password, database or host is wrong.';
					exit;
				}
			}
			elseif(strtolower($config['server']['sqlType']) == "sqlite")
			{
				//connect to SQLite database
				$link_to_sqlitedatabase = $config['site']['server_path'].$sqlitefile;
				try
				{
					$ots->connect(POT::DB_SQLITE, array('database' => $link_to_sqlitedatabase));
				}
				catch(PDOException $error)
				{
					echo 'Database error - can\'t open SQLite database. Possible reasons:<br><b>'.$link_to_sqlitedatabase.'</b> - file isn\'t valid SQLite database.<br><b>'.$link_to_sqlitedatabase.'</b> - doesn\'t exist.';
					exit;
				}
			}
			else
			{
				echo 'Database error. Unknown database type in <b>'.$config['site']['server_path'].'config.lua</b>. Must be equal to: "<b>mysql</b>" or "<b>sqlite</b>". Now is: "<b>'.strtolower($config['server']['sqlType']).'"</b>';
				exit;
			}
			$SQL = POT::getInstance()->getDBHandle();
		}
		$step = $_REQUEST['step'];
		if(empty($step)) 
		{
			$step = $config['site']['install'];
		}
		if($step == 'start') 
		{
			echo '<h1>STEP '.$step.'</h1>Informations<br>';
			echo 'Welcome to Gesior Account Maker installer. <b>First do steps 1-5 one by one, later (when you will be logged on admin account) press on links to steps 6-7 to load configuration from OTS.</b>';
		}
		if($step == '1') 
		{
			if(isset($_REQUEST['server_path'])) 
			{
				echo '<h1>STEP '.$step.'</h1>Check server configuration<br>';
				$config['site']['server_path'] = $_REQUEST['server_path'];
				$config['site']['server_path'] = trim($config['site']['server_path'])."\\";
				$config['site']['server_path'] = str_replace("\\\\", "/", $config['site']['server_path']);
				$config['site']['server_path'] = str_replace("\\", "/", $config['site']['server_path']);
				$config['site']['server_path'] = str_replace("//", "/", $config['site']['server_path']);
				saveconfig_ini($config['site']);
				if(file_exists($config['site']['server_path'].'config.lua')) {
					$config['server'] = parse_ini_file($config['site']['server_path'].'config.lua');
					if(isset($config['server']['sqlType'])) {
						$config['site']['install'] = 2;
						saveconfig_ini($config['site']);
						echo 'File <b>config.lua</b> loaded from <font color="red"><i>'.$config['site']['server_path'].'config.lua</i></font> and looks like fine server config file. Now you can check database('.$config['server']['sqlType'].') connection: <a href="install.php?page=step&step=2">STEP 2 - check database connection</a>';
					}
					else
					{
						echo 'File <b>config.lua</b> loaded from <font color="red"><i>'.$config['site']['server_path'].'config.lua</i></font> and it\'s not valid TFS config.lua file. <a href="install.php?page=step&step=1">Go to STEP 1 - select other directory.</a> If it\'s your config.lua file from TFS contact with acc. maker author.';
					}
				}
				else
				{
					echo 'Can\'t load file <b>config.lua</b> from <font color="red"><i>'.$config['site']['server_path'].'config.lua</i></font> File doesn\'t exist in selected directory. <a href="install.php?page=step&step=1">Go to STEP 1 - select other directory.</a>';
				}
			}
			else
			{
				echo 'Please write you TFS directory below. Like: <i>C:\Documents and Settings\Gesior\Desktop\trunk.r4041\</i><form action="install.php">
				<input type="text" name="server_path" size="90" value="'.$config['site']['server_path'].'" /><input type="hidden" name="page" value="step" /><input type="hidden" name="step" value="1" /><input type="submit" value="Set server path" />
				</form>';
			}
		}
		if($step == '2') 
		{
			echo '<h1>STEP '.$step.'</h1>Check database connection<br>';
			echo 'If you don\'t see any errors press <a href="install.php?page=step&step=3&server_conf=yes">link to STEP 3 - Add tables and columns to DB</a>. If you see some errors it mean server has wrong configuration. Check FAQ or ask author of acc. maker.';
			//load server config
			$config['server'] = parse_ini_file($config['site']['server_path'].'config.lua');
			if(isset($config['server']['sqlHost'])) 
			{
				$mysqlhost = $config['server']['sqlHost'];
				$mysqluser = $config['server']['sqlUser'];
				$mysqlpass = $config['server']['sqlPass'];
				$mysqldatabase = $config['server']['sqlDatabase'];
			}
			$sqlitefile = $config['server']['sqliteDatabase'];
			$encryptionType = '';
			if(strtolower($config['server']['encryptionType']) == 'md5') 
			{
				$encryptionType = 'md5';
			}
			if(strtolower($config['server']['encryptionType']) == 'sha1') 
			{
				$encryptionType = 'sha1';
			}
			// loads #####POT mainfile#####
			include('pot/OTS.php');
			// PDO and POT connects to database
			$ots = POT::getInstance();
			if(strtolower($config['server']['sqlType']) == "mysql")
			{
				//connect to MySQL database
				try
				{
					$ots->connect(POT::DB_MYSQL, array('host' => $mysqlhost, 'user' => $mysqluser, 'password' => $mysqlpass, 'database' => $mysqldatabase) );
				}
				catch(PDOException $error)
				{
					echo 'Database error - can\'t connect to MySQL database. Possible reasons:<br>1. MySQL server is not running on host.<br>2. MySQL user, password, database or host isn\'t configured in: <b>'.$config['site']['server_path'].'config.lua</b> .<br>3. MySQL user, password, database or host is wrong.';
					exit;
				}
			}
			elseif(strtolower($config['server']['sqlType']) == "sqlite")
			{
				//connect to SQLite database
				$link_to_sqlitedatabase = $config['site']['server_path'].$sqlitefile;
				try
				{
					$ots->connect(POT::DB_SQLITE, array('database' => $link_to_sqlitedatabase));
				}
				catch(PDOException $error)
				{
					echo 'Database error - can\'t open SQLite database. Possible reasons:<br><b>'.$link_to_sqlitedatabase.'</b> - file isn\'t valid SQLite database.<br><b>'.$link_to_sqlitedatabase.'</b> - doesn\'t exist.';
					exit;
				}
			}
			else
			{
				echo 'Database error. Unknown database type in <b>'.$config['site']['server_path'].'config.lua</b>. Must be equal to: "<b>mysql</b>" or "<b>sqlite</b>". Now is: "<b>'.strtolower($config['server']['sqlType']).'"</b>';
				exit;
			}
			$SQL = POT::getInstance()->getDBHandle();
			$config['site']['install'] = 3;
			saveconfig_ini($config['site']);
		}
		if($step == '3') 
		{
			echo '<h1>STEP '.$step.'</h1>Add tables and columns to DB<br>';
			echo 'Installer try to add new tables and columns to database.<br>';
				$config['server'] = parse_ini_file($config['site']['server_path'].'config.lua');
				if($config['server']['sqlType'] == "sqlite") {
					//if sqlite
					try { $SQL->query('ALTER TABLE accounts ADD "page_lastday" INTEGER(11) NOT NULL DEFAULT 0;'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE accounts ADD "email_new" VARCHAR(255) NOT NULL DEFAULT "";'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE accounts ADD "email_new_time" INTEGER(15) NOT NULL DEFAULT 0;'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE accounts ADD "created" INTEGER(11) NOT NULL DEFAULT 0;'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE accounts ADD "rlname" VARCHAR(255) NOT NULL DEFAULT "";'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE accounts ADD "location" VARCHAR(255) NOT NULL DEFAULT "";'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE accounts ADD "page_access" INTEGER(11) NOT NULL DEFAULT 0;'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE accounts ADD "email_code" VARCHAR(255) NOT NULL DEFAULT 0;'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE accounts ADD "next_email" INTEGER(11) NOT NULL DEFAULT 0;'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE accounts ADD "premium_points" INTEGER(11) NOT NULL DEFAULT 0;'); } catch(PDOException $error) {}
					echo "Added columns to table <b>accounts</b>.<br/>";
					try { $SQL->query('ALTER TABLE guilds ADD "description" TEXT NOT NULL DEFAULT "";'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE guilds ADD "logo_gfx_name" VARCHAR(255) NOT NULL DEFAULT "";'); } catch(PDOException $error) {}
					echo "Added columns to table <b>guilds</b>.<br/>";
					try { $SQL->query('ALTER TABLE players ADD "created" INTEGER(11) NOT NULL DEFAULT 0;'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE players ADD "nick_verify" VARCHAR(5) NOT NULL DEFAULT 0;'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE players ADD "hide_char" INTEGER(11) NOT NULL DEFAULT 0;'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE players ADD "comment" TEXT NOT NULL DEFAULT "";'); } catch(PDOException $error) {}
					echo "Added columns to table <b>players</b>.<br/>";
					try { $SQL->query('CREATE TABLE "z_news_big" (
								"hide_news" INTEGER NOT NULL DEFAULT 0,
								"date" INTEGER NOT NULL,
								"author" VARCHAR(255) NOT NULL,
								"author_id" INTEGER NOT NULL,
								"image_id" INTEGER NOT NULL DEFAULT 0,
								"topic" VARCHAR(255) NOT NULL,
								"text" TEXT NOT NULL);'); } catch(PDOException $error) {}
					echo "Added table <b>z_news_big</b> (news).<br/>";
					try { $SQL->query('CREATE TABLE "z_news_tickers" (
								"date" INTEGER NOT NULL,
								"author" INTEGER NOT NULL,
								"image_id" INTEGER NOT NULL DEFAULT 0,
								"text" TEXT NOT NULL,
								"hide_ticker" INTEGER NOT NULL DEFAULT 0);'); } catch(PDOException $error) {}
					echo "Added table <b>z_news_tickers</b> (tickers).<br/>";
					try { $SQL->query('CREATE TABLE "z_spells" (
								"name" VARCHAR(255) NOT NULL,
								"spell" VARCHAR(255) NOT NULL,
								"spell_type" VARCHAR(255) NOT NULL,
								"mana" INTEGER NOT NULL DEFAULT 0,
								"lvl" INTEGER NOT NULL DEFAULT 0,
								"mlvl" INTEGER NOT NULL DEFAULT 0,
								"soul" INTEGER NOT NULL DEFAULT 0,
								"pacc" VARCHAR(255) NOT NULL,
								"vocations" VARCHAR(255) NOT NULL,
								"conj_count" INTEGER NOT NULL DEFAULT 0,
								"hide_spell" INTEGER NOT NULL DEFAULT 0);'); } catch(PDOException $error) {}
					echo "Added table <b>z_spells</b> (spells list).<br/>";
					try { $SQL->query('CREATE TABLE "z_monsters" (
					  "hide_creature" INTEGER NOT NULL DEFAULT 0,
					  "name" VARCHAR(255) NOT NULL,
					  "mana" INTEGER NOT NULL,
					  "exp" INTEGER NOT NULL,
					  "health" INTEGER NOT NULL,
					  "speed_lvl" INTEGER NOT NULL DEFAULT 1,
					  "use_haste" INTEGER NOT NULL,
					  "voices" text NOT NULL,
					  "immunities" VARCHAR(255) NOT NULL,
					  "summonable" INTEGER NOT NULL,
					  "convinceable" INTEGER NOT NULL,
					  "race" VARCHAR(255) NOT NULL,
					  "gfx_name" VARCHAR(255) NOT NULL)'); } catch(PDOException $error) {}
					echo 'Added table <b>z_monsters</b> (monsters list).<br/>';
				}
				elseif($config['server']['sqlType'] == "mysql")
				{
					//if mysql
					try { $SQL->query("ALTER TABLE `accounts` ADD `premium_points` INT( 11 ) NOT NULL DEFAULT '0';"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `accounts` ADD `email_new` VARCHAR( 255 ) NOT NULL;"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `accounts` ADD `email_new_time` INT( 15 ) NOT NULL;"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `accounts` ADD `email_code` VARCHAR( 255 ) NOT NULL DEFAULT '0';"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `accounts` ADD `next_email` INT( 11 ) NOT NULL DEFAULT '0';"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `accounts` ADD `created` INT( 11 ) NOT NULL;"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `accounts` ADD `page_lastday` INT( 11 ) NOT NULL;"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `accounts` ADD `page_access` INT( 11 ) NOT NULL DEFAULT '0';"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `accounts` ADD `rlname` VARCHAR( 255 ) NOT NULL;"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `accounts` ADD `location` VARCHAR( 255 ) NOT NULL;"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `accounts` ADD `flag` VARCHAR( 255 ) NOT NULL;"); } catch(PDOException $error) {}
					echo "Added columns to table <b>accounts</b><br/>";
					try { $SQL->query('ALTER TABLE `guilds` ADD `description` TEXT NOT NULL;'); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE `guilds` ADD `logo_gfx_name` VARCHAR( 255 ) NOT NULL;'); } catch(PDOException $error) {}
					echo "Added columns to table <b>guilds</b>.<br/>";
					try { $SQL->query("ALTER TABLE `players` ADD `created` INT( 11 ) NOT NULL;"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `players` ADD `nick_verify` VARCHAR( 5 ) NOT NULL;"); } catch(PDOException $error) {}
					try { $SQL->query('ALTER TABLE `players` ADD `old_name` VARCHAR(255) NOT NULL DEFAULT "";'); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `players` ADD `hide_char` INT( 11 ) NOT NULL DEFAULT '0';"); } catch(PDOException $error) {}
					try { $SQL->query("ALTER TABLE `players` ADD `comment` TEXT NOT NULL;"); } catch(PDOException $error) {}
					echo "Added columns to table <b>players</b><br/>";
					try { $SQL->query("CREATE TABLE `z_news_big` (
							`hide_news` tinyint(1) NOT NULL DEFAULT '0',
							`date` int(11) NOT NULL DEFAULT '0',
							`author` varchar(255) NOT NULL,
							`author_id` int(11) NOT NULL,
							`image_id` int(3) NOT NULL DEFAULT '0',
							`topic` varchar(255) NOT NULL,
							`text` text NOT NULL
						) ENGINE=MyISAM DEFAULT CHARSET=latin1;"); } catch(PDOException $error) {}
					echo "Added table <b>z_news_big</b> (news).<br/>";
					try { $SQL->query("CREATE TABLE `z_news_tickers` (
							`date` int(11) NOT NULL default '1',
							`author` int(11) NOT NULL,
							`image_id` int(3) NOT NULL default '0',
							`text` text NOT NULL,
							`hide_ticker` tinyint(1) NOT NULL
						) ENGINE=MyISAM DEFAULT CHARSET=latin1;"); } catch(PDOException $error) {}
					echo "Added table <b>z_news_tickers</b> (tickers).<br/>"; 
					try { $SQL->query('CREATE TABLE `z_spells` (
							`name` VARCHAR(255) NOT NULL,
							`spell` VARCHAR(255) NOT NULL,
							`spell_type` VARCHAR(255) NOT NULL,
							`mana` INTEGER NOT NULL DEFAULT 0,
							`lvl` INTEGER NOT NULL DEFAULT 0,
							`mlvl` INTEGER NOT NULL DEFAULT 0,
							`soul` INTEGER NOT NULL DEFAULT 0,
							`pacc` VARCHAR(255) NOT NULL,
							`vocations` VARCHAR(255) NOT NULL,
							`conj_count` INTEGER NOT NULL DEFAULT 0,
							`hide_spell` INTEGER NOT NULL DEFAULT 0
						);'); } catch(PDOException $error) {}
					echo 'Added table <b>z_spells</b> (spells list).<br/>';
					try { $SQL->query('CREATE TABLE `z_monsters` (
							`hide_creature` tinyint(1) NOT NULL default \'0\',
							`name` varchar(255) NOT NULL,
							`mana` int(11) NOT NULL,
							`exp` int(11) NOT NULL,
							`health` int(11) NOT NULL,
							`speed_lvl` int(11) NOT NULL default \'1\',
							`use_haste` tinyint(1) NOT NULL,
							`voices` text NOT NULL,
							`immunities` varchar(255) NOT NULL,
							`summonable` tinyint(1) NOT NULL,
							`convinceable` tinyint(1) NOT NULL,
							`race` varchar(255) NOT NULL,
							`gfx_name` varchar(255) NOT NULL
						) ENGINE=MyISAM DEFAULT CHARSET=latin1;'); } catch(PDOException $error) {}
					echo 'Added table <b>z_monsters</b> (monsters list).<br/><br/>';
					try { $SQL->query("CREATE TABLE `z_ots_comunication` (
							`id` int(11) NOT NULL auto_increment,
							`name` varchar(255) NOT NULL,
							`type` varchar(255) NOT NULL,
							`action` varchar(255) NOT NULL,
							`param1` varchar(255) NOT NULL,
							`param2` varchar(255) NOT NULL,
							`param3` varchar(255) NOT NULL,
							`param4` varchar(255) NOT NULL,
							`param5` varchar(255) NOT NULL,
							`param6` varchar(255) NOT NULL,
							`param7` varchar(255) NOT NULL,
							`delete_it` int(2) NOT NULL default '1',
							PRIMARY KEY  (`id`)
 					    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;"); } catch(PDOException $error) {}
					echo "Added table <b>z_ots_comunication</b> (shopsystem).<br/>";
					try { $SQL->query("CREATE TABLE `z_shop_offer` (
							`id` int(11) NOT NULL auto_increment,
							`points` int(11) NOT NULL default '0',
							`itemid1` int(11) NOT NULL default '0',
							`count1` int(11) NOT NULL default '0',
							`itemid2` int(11) NOT NULL default '0',
							`count2` int(11) NOT NULL default '0',
							`offer_type` varchar(255) default NULL,
							`offer_description` text NOT NULL,
							`offer_name` varchar(255) NOT NULL,
							`pid` INT(11) NOT NULL DEFAULT '0',  
							PRIMARY KEY  (`id`)
 					    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;"); } catch(PDOException $error) {}
					echo "Added table <b>z_shop_offer</b> (shopsystem).<br/>";
					try { $SQL->query("CREATE TABLE `z_shop_history_item` (
							`id` int(11) NOT NULL auto_increment,
							`to_name` varchar(255) NOT NULL default '0',
							`to_account` int(11) NOT NULL default '0',
							`from_nick` varchar(255) NOT NULL,
							`from_account` int(11) NOT NULL default '0',
							`price` int(11) NOT NULL default '0',
							`offer_id` int(11) NOT NULL default '0',
							`trans_state` varchar(255) NOT NULL,
							`trans_start` int(11) NOT NULL default '0',
							`trans_real` int(11) NOT NULL default '0',
							PRIMARY KEY  (`id`)
 					    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;"); } catch(PDOException $error) {}
					echo "Added table <b>z_shop_history_item</b> (shopsystem).<br/>";
					try { $SQL->query("CREATE TABLE `z_shop_history_pacc` (
							`id` int(11) NOT NULL auto_increment,
							`to_name` varchar(255) NOT NULL default '0',
							`to_account` int(11) NOT NULL default '0',
							`from_nick` varchar(255) NOT NULL,
							`from_account` int(11) NOT NULL default '0',
							`price` int(11) NOT NULL default '0',
							`pacc_days` int(11) NOT NULL default '0',
							`trans_state` varchar(255) NOT NULL,
							`trans_start` int(11) NOT NULL default '0',
							`trans_real` int(11) NOT NULL default '0',
							PRIMARY KEY  (`id`)
 					    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;");  } catch(PDOException $error) {}
					echo "Added table <b>z_shop_history_pacc</b> (shopsystem).<br/>";
					try { $SQL->query("CREATE TABLE `zaypay_payment` (
							`payID` bigint(30) NOT NULL,
							`account_id` int(20) NOT NULL,
							`status` varchar(255) NOT NULL,
							PRIMARY KEY  (`payID`)
						) ENGINE=MyISAM DEFAULT CHARSET=latin1;");
					echo "Added table <b>zaypay_payment</b> (shopsystem).<br/>";
				}
				$config['site']['install'] = 4;
				saveconfig_ini($config['site']);
			echo 'All tables and columns added to database.<br>Go to <a href="install.php?page=step&step=4&server_conf=yes">STEP 4 - Add samples</a>';
		}
		if($step == '4') 
		{
			echo '<h1>STEP '.$step.'</h1>Add samples to DB:<br>';
			$check_news_ticker = $SQL->query('SELECT * FROM z_news_tickers WHERE image_id = 1 AND author = 1 AND hide_ticker = 0 LIMIT 1 OFFSET 0')->fetch();
			if(!isset($check_news_ticker['author'])) 
			{
				$SQL->query('INSERT INTO z_news_tickers (date, author, image_id, text, hide_ticker) VALUES 
														('.time().', 1, 1, "Gesior ACC - Xart prefix Edition installed!", 0)');
				echo "Added first news ticker.<br/>";
			} 
			else 
				echo "News ticker sample is already in database. New sample is not needed.<br/>";
			$check_news = $SQL->query('SELECT * FROM z_news_big WHERE author = "Gesior" LIMIT 1 OFFSET 0')->fetch();
			if(!isset($check_news['author'])) 
			{
				$SQL->query('INSERT INTO z_news_big (hide_news, date, author, author_id, image_id, topic, text) VALUES 
													(0, '.time().', "Xart", 1, 0, "Xart prefix Edition!", "Gesior ACC - Xart prefix Edition installed. All options should work fine. Report bugs on http://code.google.com/p/gesior-aac/issues/list.");');
				echo "Added first news.<br/>";
			} 
			else 
				echo "News sample is already in database. New sample is not needed.<br/>";
			$check_voc_0 = $SQL->query('SELECT * FROM players WHERE name = "Rook Sample" LIMIT 1 OFFSET 0')->fetch();
			if(!isset($check_voc_0['name'])) 
			{
				$SQL->query('INSERT INTO players 	(name, world_id, group_id, account_id, level, vocation, health, healthmax, experience, lookbody, lookfeet, lookhead, looklegs, looktype, lookaddons, maglevel, mana, manamax, manaspent, soul, town_id, posx, posy, posz, conditions, cap, sex, lastlogin, lastip, save, skull, skulltime, rank_id, guildnick, lastlogout, blessings, balance, stamina, direction, loss_experience, loss_mana, loss_skills, loss_containers, loss_items, premend, online, marriage, promotion, deleted, description, comment, created, hide_char, nick_verify) VALUES 
													("Rook Sample", 0, 1, 1, 1, 0, 150, 150, 0, 44, 44, 44, 44, 128, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, "", 420, 1, "", "", 1, "", "", "", "", "", "", "", 151200000, "", "", "", "", "", "", "", "", "", "", "", "", "",'.time().',1,1)');
				echo "Added 'Rook Sample' character.<br/>";
			} 
			else 
				echo "Character 'Rook Sample' already in database.<br/>";
			$check_voc_1 = $SQL->query('SELECT * FROM players WHERE name = "Sorcerer Sample" LIMIT 1 OFFSET 0')->fetch();
			if(!isset($check_voc_1['name'])) 
			{
				$SQL->query('INSERT INTO players 	(name, world_id, group_id, account_id, level, vocation, health, healthmax, experience, lookbody, lookfeet, lookhead, looklegs, looktype, lookaddons, maglevel, mana, manamax, manaspent, soul, town_id, posx, posy, posz, conditions, cap, sex, lastlogin, lastip, save, skull, skulltime, rank_id, guildnick, lastlogout, blessings, balance, stamina, direction, loss_experience, loss_mana, loss_skills, loss_containers, loss_items, premend, online, marriage, promotion, deleted, description, comment, created, hide_char, nick_verify) VALUES 
													("Sorcerer Sample", 0, 1, 1, 1, 1, 150, 150, 0, 44, 44, 44, 44, 128, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, "", 420, 1, "", "", 1, "", "", "", "", "", "", "", 151200000, "", "", "", "", "", "", "", "", "", "", "", "", "",'.time().',1,1)');
				echo "Added 'Sorcerer Sample' character.<br/>";
			} 
			else 
				echo "Character 'Sorcerer Sample' already in database.<br/>";
			$check_voc_2 = $SQL->query('SELECT * FROM players WHERE name = "Druid Sample" LIMIT 1 OFFSET 0')->fetch();
			if(!isset($check_voc_2['name'])) 
			{
				$SQL->query('INSERT INTO players 	(name, world_id, group_id, account_id, level, vocation, health, healthmax, experience, lookbody, lookfeet, lookhead, looklegs, looktype, lookaddons, maglevel, mana, manamax, manaspent, soul, town_id, posx, posy, posz, conditions, cap, sex, lastlogin, lastip, save, skull, skulltime, rank_id, guildnick, lastlogout, blessings, balance, stamina, direction, loss_experience, loss_mana, loss_skills, loss_containers, loss_items, premend, online, marriage, promotion, deleted, description, comment, created, hide_char, nick_verify) VALUES 
													("Druid Sample", 0, 1, 1, 1, 2, 150, 150, 0, 44, 44, 44, 44, 128, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, "", 420, 1, "", "", 1, "", "", "", "", "", "", "", 151200000, "", "", "", "", "", "", "", "", "", "", "", "", "",'.time().',1,1)');
				echo "Added 'Druid Sample' character.<br/>";
			} 
			else 
				echo "Character 'Druid Sample' already in database.<br/>";
			$check_voc_3 = $SQL->query('SELECT * FROM players WHERE name = "Paladin Sample" LIMIT 1 OFFSET 0')->fetch();
			if(!isset($check_voc_3['name'])) 
			{
				$SQL->query('INSERT INTO players 	(name, world_id, group_id, account_id, level, vocation, health, healthmax, experience, lookbody, lookfeet, lookhead, looklegs, looktype, lookaddons, maglevel, mana, manamax, manaspent, soul, town_id, posx, posy, posz, conditions, cap, sex, lastlogin, lastip, save, skull, skulltime, rank_id, guildnick, lastlogout, blessings, balance, stamina, direction, loss_experience, loss_mana, loss_skills, loss_containers, loss_items, premend, online, marriage, promotion, deleted, description, comment, created, hide_char, nick_verify) VALUES 
													("Paladin Sample", 0, 1, 1, 1, 3, 150, 150, 0, 44, 44, 44, 44, 128, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, "", 420, 1, "", "", 1, "", "", "", "", "", "", "", 151200000, "", "", "", "", "", "", "", "", "", "", "", "", "",'.time().',1,1)');
				echo "Added 'Paladin Sample' character.<br/>";
			} 
			else 
				echo "Character 'Paladin Sample' already in database.<br/>";
			$check_voc_4 = $SQL->query('SELECT * FROM players WHERE name = "Knight Sample" LIMIT 1 OFFSET 0')->fetch();
			if(!isset($check_voc_4['name'])) 
			{
				$SQL->query('INSERT INTO players 	(name, world_id, group_id, account_id, level, vocation, health, healthmax, experience, lookbody, lookfeet, lookhead, looklegs, looktype, lookaddons, maglevel, mana, manamax, manaspent, soul, town_id, posx, posy, posz, conditions, cap, sex, lastlogin, lastip, save, skull, skulltime, rank_id, guildnick, lastlogout, blessings, balance, stamina, direction, loss_experience, loss_mana, loss_skills, loss_containers, loss_items, premend, online, marriage, promotion, deleted, description, comment, created, hide_char, nick_verify) VALUES 
													("Knight Sample", 0, 1, 1, 1, 4, 150, 150, 0, 44, 44, 44, 44, 128, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, "", 420, 1, "", "", 1, "", "", "", "", "", "", "", 151200000, "", "", "", "", "", "", "", "", "", "", "", "", "",'.time().',1,1)');
				echo "Added 'Knight Sample' character.<br/>";
			} 
			else 
				echo "Character 'Knight Sample' already in database.<br/>";
			$config['site']['install'] = 5;
			saveconfig_ini($config['site']);
			echo 'All samples added to database. Now you can go to <a href="install.php?page=step&step=5&server_conf=yes">STEP 5 - Set Admin Account</a><br/>';
		}
		if($step == '5') 
		{
			echo '<h1>STEP '.$step.'</h1>Set Admin Account<br>';
			$config['server'] = parse_ini_file($config['site']['server_path'].'config.lua');
			if(empty($_REQUEST['saveaccpassword'])) 
			{
				echo 'Admin account number is: <b>1</b><br/>Set new password to this account.<br>';
				echo 'New password: <form action="install.php" method=POST><input type="text" name="newpass" size="35">(Don\'t give it password to anyone!)';
				echo '<input type="hidden" name="saveaccpassword" value="yes"><input type="hidden" name="page" value="step"><input type="hidden" name="step" value="5"><input type="submit" value="SET"></form><br>If account with number 1 doesn\'t exist installator will create it and set your password.';
			} 
			else 
			{
				$newpass = $_POST['newpass'];
				if(!check_password($newpass))
					echo 'Password contains illegal characters. Please use only a-Z and 0-9. <a href="install.php?page=step&step=5&server_conf=yes">GO BACK</a> and write other password.';
				else
				{
					$newpass_to_db = password_ency($newpass);
					$account = new OTS_Account();
					$account->load(1);
					if($account->isLoaded()) 
					{
						$account->setPassword($newpass_to_db);
						$account->save();
						$account->setCustomField("page_access", 6);
					} 
					else
					{
						$number = $account->create(1,1);
						$account->setPassword($newpass_to_db);
						$account->unblock();
						$account->save();
						$account->setCustomField("page_access", 6);
					}
					$_SESSION['account'] = 1;
					$_SESSION['password'] = $newpass;
					$logged = TRUE;
					$account->setCustomField("page_lastday", time());
					echo '<h1>Admin account number: 1<br>Admin account password: '.$_POST['newpass'].'</h1><br/><h3>It\'s end of first part of installation. Installation is blocked. From now don\'t modify file config.ini!<br>Press links to STEPs 6 and 7 in menu.</h3>'; 
					$config['site']['install'] = 'no';
					saveconfig_ini($config['site']);
				}
			}
		}
	}
	else
	{
		echo "Account maker is already installed! To reinstall open file 'config.ini' in directory 'config' and change:<br/><b>install = \"no\"</b><br/>to:</br><b>install = \"start\"</b><br/>and enter this site again.";
	}
}
?>