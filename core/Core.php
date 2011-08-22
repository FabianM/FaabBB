<?php 
// Check if the Core is already imported.
if (defined('FaabBB'))
	exit();
	
// We define this constant, so Core classes can check that they are imported by the core.
define('FaabBB', true);

// Other constants.
define('FaabBB_VERSION', '3.009 ALPHA');
define('PHP_VERSION_NEEDED', '5.3.0');

define('PHP_SUFFIX', '.php');
define('INI_SUFFIX', '.ini');

// Define the directory seperator. 
define('DS', DIRECTORY_SEPARATOR);

define('ROOT', dirname(__FILE__)  . DS .  '..'); 
define('CORE_FOLDER', ROOT . DS . 'core');
define('CORE_LIBRARY_FOLDER', CORE_FOLDER . DS . 'lib');
define('CLASSES_FOLDER', ROOT . DS . 'classes' . DS); 
define('APP_FOLDER', ROOT . DS . 'app' . DS); 
define('DATA_FOLDER', ROOT . DS . 'data');
define('CONFIGURATION_FOLDER', DATA_FOLDER . DS . 'config');
define('LOGS_FOLDER', DATA_FOLDER . DS . 'logs');
define('CORE_LOG_FILE', LOGS_FOLDER . DS . 'core.log' 
	. PHP_SUFFIX);
define('ERROR_HANDLING_METHOD', "CoreErrorHandler::onError");
define('EXCEPTION_HANDLING_METHOD', "CoreErrorHandler::onException");
define('SHUTDOWN_HANDLING_METHOD', "CoreErrorHandler::onShutdown");
define('DEBUG', 2);

// Here we import the core classes.
include_once(CORE_FOLDER . DS . 'CoreConfiguration' . PHP_SUFFIX);
include_once(CORE_FOLDER . DS . 'CoreState' . PHP_SUFFIX);
include_once(CORE_FOLDER . DS . 'CoreLogger' . PHP_SUFFIX);
include_once(CORE_FOLDER . DS . 'CoreException' . PHP_SUFFIX);
include_once(CORE_FOLDER . DS . 'CoreErrorHandler' . PHP_SUFFIX);


/**
 * Represents the <code>core</code> of this webpage. The {@link Core} class will receive 
 * 	incomming requests and handle them.
 * The core will load the {@link Core} components and will check for errors. The {@link Core} class 
 * 	will never use any files of the FaabBB class library, so the {@link Core} uses 
 * 	pre-defined functions, variables and classes that comes along with the PHP function library 
 * 	that can be found at <a href="http://php.net/manual/en/funcref.php">http://php.net/manual/en/funcref.php</a> or
 * 	classes from the Core library.
 * The {@link Core} uses a static pattern which means there's only one {@link Core}. 
 * 
 * @category Core
 * @version Version 3.009 ALPHA
 * @copyright Copyright &copy; 2011, FaabTech
 * @author Fabian M.
 */
class Core {
	
	/**
	 * This variable contains the {@link State} we're in. 
	 */
	public static $STATE = CoreState::INIT;
	
	/**
	 * Updates the current core state.
	 * 
	 * @param $state The state to update to.
	 */
	public static function checkpoint($state) {
		self::$STATE = $state;
	}
	 
	
	
	/**
	 * Initializes the {@link Core} for use.
	 * This method will load the <code>core</code> libraries and components, define global constants, 
	 * 	load the Command line interface and finally loads the FaabBB class library.
	 * This method should not be invoked by any classes. Ofcourse FaabBB will check this.
	 * 
	 * @since Version 3.006 ALPHA
	 */
	 public static function init() {	
	 	if (self::$STATE != CoreState::INIT) {
	 		// Method invoked when Core is already initialized.
	 		CoreLogger::warning("Core::init() invoked when Core is already initialized.");
	 		return;
	 	}
	 	CoreLogger::info("Loading FaabBB " . FaabBB_VERSION);
	 	CoreLogger::info("Checking PHP version..");
	 	if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
	 		CoreLogger::fine("PHP version is " . PHP_VERSION . ". Fine..");
	 	} else {
	 		CoreLogger::severe("PHP version must be higher than 5.3.0. Yours is " . PHP_VERSION);
	 		exit();
	 	}
	 	CoreLogger::info("Changing error reporting level.");
	 	error_reporting((DEBUG == 2 ? -1 : 0));
	 	CoreLogger::info("Done changing error reporting level to " . DEBUG . ".");
	 	CoreLogger::info("Initializing CoreErrorHandler.");
	 	CoreErrorHandler::init();
	 	//CoreLogger::info("Intializing CoreLibraryLoader.");
	 	//CoreLibraryLoader::init();
	 	CoreLogger::info("Initializing configuration loader.");
	 	CoreConfiguration::getInstance()->init();
	 	
	 	 
	 	// Successfully initialized.
	 	self::checkpoint(CoreState::INVOKE);
	 }
	 
	 /**
	  * The {@link Core#invoke} method will read, parse
	  * 	and invoke.
	  * When the command <code>FaabBB</code> is used
	  * 	without any options. The core will execute
	  * 	normally.
	  * Otherwise a custom command will be invoked.
	  * 
	  * @since Version 3.006 ALPHA
	  */
	  public static function invoke() {
	  	// Can not invoke.. Core state is wrong.
	  	if (self::$STATE != CoreState::INVOKE) {
	 		CoreLogger::warning("Can not invoke.. Core state is wrong.");
	 		return; 
	 	}
	 	$run = CoreConfiguration::getInstance()->run;
	 	if ($run == null || empty($run)) 
	 		throw new CoreException("Nothing to run...");
	 	
	 	$explode = explode(" ", $run);
	 	$file = $explode[0];
	 	$args = array();
	 	
	 	for ($i = 1; $i < count($explode); $i++)
	 		$args[($i - 1)] = $explode[$i];
	 	$aFile = CLASSES_FOLDER . DS . $file . PHP_SUFFIX;
	 	
	 	if (!file_exists($aFile))
	 		throw new CoreException("File not exists " . $aFile);
	 		
	 	include($aFile);
	 	
	 	$info = pathinfo($aFile);
	 	$clsName = $info['filename'];
	 	
	 	if (!class_exists($clsName))
	 		throw new CoreException("Class " . $clsName . " does not exists.");
	 		
	 	$reflector = new ReflectionClass($clsName);
	 	
	 	if (!$reflector) 
	 		throw new CoreException("Failed to get main class: " . $clsName);
	 		
	 	if (!$reflector->hasMethod('main'))
	 		throw new CoreException("Method main not found in class " . $clsName);
	 		
	 	$reflector->getMethod('main')->invoke(null, $args);
	 	
	  	self::checkpoint(CoreState::SUCCESS);
	  }
	
	
}

// Initialize the Core after defining the Core class.
Core::init();

// Define autoloader.
/**
 * Autoload not imported classes.
 * <note>This will only autoload the php.lang library</note>
 * 
 * @param $class_name The name of the class to auto import.
 */
function __autoload($class_name) {
	if (!class_exists($class_name)) {
		$path = CLASSES_FOLDER . DS . 'php' . DS . 'lang' . 
			DS . $class_name . PHP_SUFFIX;
		if (!file_exists($path))
			throw new CoreException("File not found: " . $path . ". Failed to import.");
			
		include($path);
	}
}

