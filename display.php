<?php
/**
 * The display controller for Pines. Handles ouput.
 *
 * @package Pines
 * @subpackage Core
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html
 * @author Hunter Perrin <hunter@sciactive.com>
 * @copyright Hunter Perrin
 * @link http://sciactive.com/
 */
defined('P_RUN') or die('Direct access prohibited');

/**
 * The current template.
 * 
 * @global string $config->current_template
 */
$config->current_template = ( !empty($_REQUEST['template']) && $config->allow_template_override ) ?
	$_REQUEST['template'] : $config->default_template;
require_once('templates/'.$config->current_template.'/configure.php');

/**
 * Modules are blocks of code or data to be placed on the page.
 * @package Pines
 */
class module {
    /**
     * The modules title.
     * @var string $title
     */
	public $title = '';
    /**
     * A suffix to append to the module's class name. Applies to HTML modules.
     * @var string $class_suffix
     */
	public $class_suffix = '';
    /**
     * The modules content.
     *
     * Though not necessary, this should be filled using a view.
     * @var string $content
     */
	public $content = '';
    /**
     * The component that the module will retrieve its content from.
     * @var string $component
     */
	public $component = '';
    /**
     * The view that the module will retrieve its content from.
     * @var string $view
    */
	public $view = '';
    /**
     * The position on the page to place the module.
     * @var string $position
     */
    public $position = null;
    /**
     * The order the module will be placed in.
     * @var int $order
     */
    public $order = null;
    /**
     * Whether the title of the module should be displayed.
     * @var bool $show_title
     */
	public $show_title = true;

    /**
     * @param string $component
     * @uses module::$component
     * @param string $view
     * @uses module::$view
     * @param string $position
     * @param int $order
     * @uses module::attach()
     * @return mixed If $position is given, returns the value of attach($position, $order).
     */
	function __construct($component, $view, $position = null, $order = null) {
        $this->component = $component;
        $this->view = $view;
		if ( !is_null($position) ) {
			return $this->attach($position, $order);
		}
	}

    /**
     * Attach the module to a position on the page.
     *
     * @global page Used to attach a module.
     * @param string $position
     * @uses module::$position
     * @param int $order
     * @uses module::$order
     * @uses page::attach_module()
     * @return int The order in which the module was placed.
     */
	function attach($position, $order = null) {
		global $page;
        $this->position = $position;
        $this->order = $page->attach_module($this, $position, $order);
		return $this->order;
	}

    /**
     * Detach the module from the page.
     *
     * @global page Used to detach a module.
     * @uses page::detach_module()
     * @return mixed The value of $page->detach_module.
     */
    function detach() {
		global $page;
		return $page->detach_module($this, $this->position, $this->order);
    }

    /**
     * Append content to the module.
     * 
     * Note that this may be appended before the view is called, thus being
     * placed before the content from the view.
     *
     * @param string $add_content Content to append.
     */
	function content($add_content) {
		$this->content .= $add_content;
	}

    /**
     * Retrieve the current content of the module.
     * 
     * Note that this may not include the content generated by the view if not
     * called late enough.
     *
     * @return string The content.
     */
	function get_content() {
		return $this->content;
	}

    /**
     * Renders the module.
     * 
     * render() will first try to find the view in a folder named as the format
     * defined in the template, then will remove text after and including the
     * last dash in the format until it finds a view. If nothing is found after
     * the last dash is removed, it will require() the view from the directory
     * 'all'.
     *
     * For example, if the component is 'com_game' and the view is 'stats', and
     * the templates type is 'xhtml-1.0-strict', render() will try the following
     * files in order:
     *
     * components/com_game/views/xhtml-1.0-strict/stats.php
     * components/com_game/views/xhtml-1.0/stats.php
     * components/com_game/views/xhtml/stats.php
     * components/com_game/views/all/stats.php
     *
     * The component 'system' has views in system/views/. The view 'null' in
     * 'system' can be used as a blank view.
     *
     * The module's template is found in the 'models' directory of the current
     * template. The module's content ultimately end up with the output from
     * this file.
     *
     * @global DynamicConfig
     * @global page
     */
	function render() {
        global $config, $page;
        
        // Get content from the view.
        ob_start();
        $format = $config->template->format;
        while(true) {
            $filename = (($this->component != 'system') ? 'components/' : '').$this->component.'/views/'.$format.'/'.$this->view.'.php';
            if (file_exists($filename) || $format == 'all') {
                require $filename;
                break;
            } else {
                if (strrpos($format, '-') === false) {
                    $format = 'all';
                } else {
                    $format = substr($format, 0, strrpos($format, '-'));
                }
            }
        }
        $this->content(ob_get_clean());

        // Return the content.
        ob_start();
        require 'templates/'.$config->current_template.'/models/module.php';
        $this->content = ob_get_clean();
	}
}

/**
 * The controller of the page. It controls what is output to the user.
 * @package Pines
 */
class page {
    /**
     * The page's title.
     * @var string $title
     * @internal
     */
	protected $title = '';
    /**
     * The head section of the page.
     * @var string $head
     * @internal
     */
	protected $head = '';
    /**
     * The body section of the page.
     * @var string $content
     * @internal
     */
	protected $content = '';
    /**
     * The footer at the bottom of the page.
     * @var string $footer
     * @internal
     */
	protected $footer = '';
    /**
     * The content which will override the entire page.
     * @var string $override_doc
     * @internal
     */
	protected $override_doc = '';
    /**
     * The notices to display.
     * @var array $notice
     * @internal
     */
	protected $notice = array();
    /**
     * The errors to display.
     * @var array $error
     * @internal
     */
	protected $error = array();
    /**
     * The moduels to display.
     * @var array $modules
     * @internal
     */
	protected $modules = array();
    /**
     * Wether to override the output of the page and display custom content.
     *
     * @var bool $override
     */
	public $override = false;
    /**
     * The page's main menu. Used to navigate the system.
     *
     * @var menu $main_menu
     */
	public $main_menu = NULL;

    /**
     * Initialize the main menu.
     *
     * @internal
     */
	public function __construct() {
		$this->main_menu = new menu;
	}

    /**
     * Append text to the title of the page.
     *
     * @param string $add_title Text to append.
     */
	public function title($add_title) {
		$this->title .= $add_title;
	}

    /**
     * Get the title of the page. If the title has not been explicitly set,
     * get_title() uses $config->option_title.
     *
     * @global DynamicConfig
     * @return string The title.
     */
	public function get_title() {
		global $config;
		if ( !empty($this->title) ) {
			return $this->title;
		} else {
			return $config->option_title;
		}
	}

    /**
     * Append text to the head section of the page.
     *
     * @param string $add_head Text to append.
     */
	public function head($add_head) {
		$this->head .= $add_head;
	}

    /**
     * Get the head section of the page.
     *
     * @return string The head section.
     */
	public function get_head() {
		return $this->head;
	}

    /**
     * Add a notice to be displayed to the user.
     *
     * @param string $message The message text.
     * @param string $image The filename of an image to use.
     * @todo Image support.
     */
	public function notice($message, $image = NULL) {
		$this->notice[] = $message;
	}

    /**
     * Get the array of notices.
     *
     * @return array The array.
     */
	public function get_notice() {
		return $this->notice;
	}

    /**
     * Add an error to be displayed to the user.
     *
     * @param string $message The message text.
     * @param string $image The filename of an image to use.
     * @todo Image support.
     */
	public function error($message, $image = NULL) {
		$this->error[] = $message;
	}

    /**
     * Get the array of errors.
     *
     * @return array The array.
     */
	public function get_error() {
		return $this->error;
	}

    /**
     * Append text to the body of the page. This may not be supported by some
     * templates, so try to avoid using it. It may also appear on in any part of
     * the body. You can use a module with the system/false view instead.
     *
     * @param string $add_content Text to append.
     */
	public function content($add_content) {
		$this->content .= $add_content;
	}

    /**
     * Get the text appended to the body of the page.
     *
     * @return string The body text.
     */
	public function get_content() {
		return $this->content;
	}

    /**
     * Attach a module to a position on the page. The $order parameter is not
     * guaranteed, and will be ignored if that place is already taken.
     *
     * @param module $module The module to attach.
     * @param string $position The position on the page. Templates can define their own positions.
     * @param int $order The order in which to try to place the module.
     * @return int The order in which the module was placed. This will be the last key + 1 if the desired order is already taken.
     */
	public function attach_module(&$module, $position, $order = null) {
		if ( is_null($order) ) {
			if ( isset($this->modules[$position]) ) {
                end($this->modules[$position]);
                $order = key($this->modules[$position]) + 1;
			} else {
				$order = 0;
			}
		} else {
			if (isset($this->modules[$position])) {
				if ( isset($this->modules[$position][$order]) ) {
                    end($this->modules[$position]);
					$order = key($this->modules[$position]) + 1;
                }
			}
		}
		$this->modules[$position][$order] = $module;
		return $order;
	}

    /**
     * Deletes a module from the list of attached modules. It will try the
     * module at $order or if $order is null then last one in $position, then
     * iterate through $position searching for the module. It will delete the
     * first match it finds, then stop and return true.
     * 
     * @param module $module The module to search for.
     * @param string $position The position in which to search.
     * @param int $order The order to try first.
     * @return bool Whether a matching module was found and successfully deleted.
     */
	public function detach_module(&$module, $position, $order = null) {
		if ( is_null($order) ) {
			if ( isset($this->modules[$position]) ) {
                end($this->modules[$position]);
                $order = key($this->modules[$position]);
			} else {
				return false;
			}
		}
		if ($this->modules[$position][$order] == $module) {
            unset($this->modules[$position][$order]);
            return true;
        } else {
            foreach ($this->modules[$position] as $key => $cur_module) {
                if ($this->modules[$position][$key] == $module) {
                    unset($this->modules[$position][$key]);
                    return true;
                }
            }
            return false;
        }
	}

    /**
     * Append text to the footer of the page.
     *
     * @param string $add_footer Text to append.
     */
	public function footer($add_footer) {
		$this->footer .= $add_footer;
	}

    /**
     * Get the footer of the page.
     *
     * @return string The footer.
     */
	public function get_footer() {
		return $this->footer;
	}

    /**
     * Append text to the override document. Use this function to supply output
     * if you are overriding the document.
     *
     * @param string $add_body Text to append.
     */
	public function override_doc($add_body) {
		$this->override_doc .= $add_body;
	}

    /**
     * Get the override document.
     *
     * @return string The head section.
     */
	public function get_override_doc() {
		return $this->override_doc;
	}

    /**
     * Render the page.
     * 
     * It will first render all the modules, then require() the template.php
     * file in the current template. However, render() will display the result
     * of get_override_doc() if $page->override is true.
     *
     * @global mixed Declare all globals in the function so they are available in the template.
     * @uses page::$override
     */
	public function render() {
        // Render each module. This will fill in the head section of the page.
        foreach ($this->modules as $cur_position) {
            foreach ($cur_position as $cur_module) {
                $cur_module->render();
            }
        }

        // Make all globals accessible, so the template file can use them.
		foreach ($GLOBALS as $key => $val) { global $$key; }
		if ( $this->override ) {
			echo $this->get_override_doc();
		} else {
			require("templates/".$config->current_template."/template.php");
		}
	}

    /**
     * Render the modules in a position.
     *
     * @param string $position The position to work on.
     * @return string The content rendered by the modules.
     */
    public function render_modules($position) {
        $return = '';
		foreach ($this->modules[$position] as $cur_module) {
			$return .= $cur_module->get_content() . "\n";
		}
        return $return;
    }
}

/*
class table {
	public $name, $id, $class, $style = '';
	public $table_array = array();

	/*
	 * $info can contain:
	 * type: th, td, or tf
	 * index: an integer
	 * name: a string
	 * id: a string
	 * class: a string
	 * style: a string
	 *
	function add_row($cells = array(), $info = array('type' => 'td')) {
		if ( isset($info['index']) ) {
			//TODO: finish this table class
		}
	}

	function render() {
		$return = '<table';
		$return .= empty($this->name) ? '' : ' name="'.$this->name.'"';
		$return .= empty($this->id) ? '' : ' id="'.$this->id.'"';
		$return .= empty($this->class) ? '' : ' class="'.$this->class.'"';
		$return .= empty($this->style) ? '' : ' style="'.$this->style.'"';
		$return .= ">\n";
	}
}
 */

/**
 * A menu.
 * @package Pines
 */
class menu {
    /**
     * The menu's array of entries.
     *
     * @var array
     */
	public $menu = array();

    /**
     * Add an item to or overwrite an entry in the menu.
     *
     * @param string $name The name of the entry.
     * @param string $data The data of the entry. Usually this is the URL to which the entry will point.
     * @param int $father The parent entry.
     * @param int $id The ID of the entry. This should only be set if you are overwriting another entry.
     * @return int The ID of the new entry.
     */
	function add($name, $data = '#', $father = NULL, $id = NULL) {
		if ( is_null($id) )
			$id = count($this->menu);
		$this->menu[$id]['name'] = $name;
		$this->menu[$id]['data'] = $data;
		$this->menu[$id]['father'] = $father;
		return $id;
	}

    /**
     * Renders the menu.
     *
     * @param array $top_container The containing element of the menu.
     * @param array $top_element The element of each toplevel entry.
     * @param array $sub_container The containing element of each sublevel entry in the menu.
     * @param array $sub_element The element of each sublevel entry.
     * @param string $link The link code for each entry. The text "#NAME#" and "#DATA#" will be replaced by the name and data of the entry respectively.
     * @param string $post_code Any code which will be appended to the completed menu.
     * @uses menu::render_item()
     * @return string The rendered code.
     */
	function render($top_container = array('<ul class="dropdown dropdown-horizontal">', '</ul>'), $top_element = array('<li>', '</li>'), $sub_container = array('<ul>', '</ul>'), $sub_element = array('<li>', '</li>'), $link = '<a href="#DATA#">#NAME#</a>', $post_code = '<hr style="visibility: hidden; clear: both;" />') {
		$return = '';
		if ( empty($this->menu) ) return $return;
		$return .= $top_container[0];
		foreach ($this->menu as $cur_id => $cur_item) {
			if ( is_null($cur_item['father']) ) {
				$return .= $top_element[0];
				$cur_link = str_replace('#DATA#', $cur_item['data'], $link);
				$cur_link = str_replace('#NAME#', $cur_item['name'], $cur_link);
				$return .= $cur_link;
				$return .= $this->render_item($cur_id, $sub_container, $sub_element, $link);
				$return .= $top_element[1];
			}
		}
		$return .= $top_container[1];
		$return .= $post_code;
		return $return;
	}

    /**
     * Render an entry (and children) of the menu.
     *
     * @param int $id The entry's ID.
     * @param array $sub_container The containing element of each entry.
     * @param array $sub_element The element of each entry.
     * @param string $link The link code for each entry. The text "#NAME#" and "#DATA#" will be replaced by the name and data of the entry respectively.
     * @return string The rendered entry.
     */
	function render_item($id, $sub_container, $sub_element, $link) {
		$return = '';
		foreach ($this->menu as $cur_id => $cur_item) {
			if ( $cur_item['father'] === $id ) {
				$return .= $sub_element[0];
				$cur_link = str_replace('#DATA#', $cur_item['data'], $link);
				$cur_link = str_replace('#NAME#', $cur_item['name'], $cur_link);
				$return .= $cur_link;
				$return .= $this->render_item($cur_id, $sub_container, $sub_element, $link);
				$return .= $sub_element[1];
			}
		}
		if ( !empty($return) )
		$return = $sub_container[0] . $return . $sub_container[1];
		return $return;
	}

    /**
     * Find all the orphaned entries in the menu.
     *
     * @return array An array of entries.
     */
	function orphans() {
		$return = array();
		foreach ($this->menu as $cur_id => $cur_item) {
			if ( !is_null($cur_item['father']) && !isset($this->menu[$cur_item['father']]) ) {
				$return[$cur_id] = $cur_item;
			}
		}
		return $return;
	}
}

/**
 * The page controller's variable. One of the few objects not under $config.
 * @global page $page
 */
$page = new page;

?>