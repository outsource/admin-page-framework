<?php
if ( ! class_exists( 'AdminPageFramework_Menu' ) ) :
/**
 * Provides methods to manipulate menu items.
 *
 * @abstract
 * @since			2.0.0
 * @extends			AdminPageFramework_Page
 * @package			Admin Page Framework
 * @subpackage		Admin Page Framework - Page
 * @staticvar		array	$_aBuiltInRootMenuSlugs	stores the WordPress built-in menu slugs.
 * @staticvar		array	$_aStructure_SubMenuPageForUser	represents the structure of the sub-menu page array.
 */
abstract class AdminPageFramework_Menu extends AdminPageFramework_Page {
	
	/**
	 * Used to refer the built-in root menu slugs.
	 * 
	 * @since			2.0.0
	 * @remark			Not for the user.
	 * @var				array			Holds the built-in root menu slugs.
	 * @static
	 * @internal
	 */ 
	protected static $_aBuiltInRootMenuSlugs = array(
		// All keys must be lower case to support case insensitive look-ups.
		'dashboard' => 			'index.php',
		'posts' => 				'edit.php',
		'media' => 				'upload.php',
		'links' => 				'link-manager.php',
		'pages' => 				'edit.php?post_type=page',
		'comments' => 			'edit-comments.php',
		'appearance' => 		'themes.php',
		'plugins' => 			'plugins.php',
		'users' => 				'users.php',
		'tools' => 				'tools.php',
		'settings' => 			'options-general.php',
		'network admin' => 		"network_admin_menu",
	);		

	/**	
	 * Represents the structure of the sub-menu link array for the users.
	 * @since			2.0.0
	 * @since			2.1.4			Changed to be static since it is used from multiple classes.
	 * @since			3.0.0			Moved from the link class.
	 * @remark			The scope is public because this is accessed from an extended class.
	 */ 
	protected static $_aStructure_SubMenuLinkForUser = array(		
		'type' => 'link',	
		'title' => null,	// required
		'href' => null,		// required
		'capability' => null,	// optional
		'order' => null,	// optional
		'show_page_heading_tab' => true,
		'show_in_menu' => true,
	);
	
	/**
	 * Represents the structure of the sub-menu link array for the system.
	 * 
	 * @since			3.0.0
	 */
	// protected static $_aStructure_SubMenuLinkForSystem = array(
		// 'sTitle' => null,
		// 'sHref' => null,
		// 'capability' => null,
		// 'nOrder' => null,
		// 'sType' => 'link',
		// 'fShowPageHeadingTab' => true,
		// 'fShowInMenu' => true,	
	// );
	
	/**
	 * Represents the structure of sub-menu page array for the users.
	 * 
	 * @since			2.0.0
	 * @remark			Not for the user.
	 * @var				array			Holds array structure of sub-menu page.
	 * @static
	 * @internal
	 */ 
	protected static $_aStructure_SubMenuPageForUser = array(
		'type'						=> 'page',	// this is used to compare with the link type.
		'title'						=> null, 
		'page_slug'					=> null, 
		'screen_icon'				=> null,	// this will become either href_icon_32x32 or screen_icon_id
		'capability'				=> null, 
		'order'						=> null,
		'show_page_heading_tab'		=> true,	// if this is false, the page title won't be displayed in the page heading tab.
		'show_in_menu' 				=> true,	// if this is false, the menu label will not be displayed in the sidebar menu.		
		'href_icon_32x32'			=> null,
		'screen_icon_id'			=> null,
		// 'show_menu'					=> null,	<-- not sure what this was for.
		'show_page_title'			=> null,
		'show_page_heading_tabs'	=> null,
		'show_in_page_tabs'			=> null,
		'in_page_tab_tag'			=> null,
		'page_heading_tab_tag'		=> null,
	);
	
	/**
	 * Represents the structure of the sub-menu page array for the system.
	 * 
	 * @since			3.0.0
	 */	
	// protected static $_aStructure_SubMenuPageForSystem = array(
		// 'sTitle' => null,
		// 'sPageSlug' => null,
		// 'sType' => 'page',
// 'sIcon32x32' => null,
// 'sScreenIconID' => null,
// 'capability' => null, 		
// 'nOrder' => null,
// 'fShowPageHeadingTab' => true,
// 'fShowInMenu' => true,		
// 'show_page_title'			=> null,			// boolean
// 'fShowPageHeadingTabs'		=> null,		// boolean
// 'fShowInPageTabs'			=> null,			// boolean
// 'sInPageTabTag'				=> null,			// string
// 'sPageHeadingTabTag'		=> null,		// string			
	// );
	 
	function __construct() {
		
		add_action( 'admin_menu', array( $this, '_replyToBuildMenu' ), 98 );		
		
		// Call the parent constructor.
		$aArgs = func_get_args();
		call_user_func_array( array( $this, "parent::__construct" ), $aArgs );
		
	} 
	 
	/**
	 * Sets to which top level page is going to be adding sub-pages.
	 * 
	 * <h4>Example</h4>
	 * <code>$this->setRootMenuPage( 'Settings' );</code>
	 * <code>$this->setRootMenuPage( 
	 * 	'APF Form',
	 * 	plugins_url( 'image/screen_icon32x32.jpg', __FILE__ )
	 * );</code>
	 * 
	 * @acecss			public
	 * @since			2.0.0
	 * @since			2.1.6			The $sIcon16x16 parameter accepts a file path.
	 * @since			3.0.0			The scope was changed to public from protected.
	 * @remark			Only one root page can be set per one class instance.
	 * @param			string			$sRootMenuLabel			If the method cannot find the passed string from the following listed items, it will create a top level menu item with the passed string. ( case insensitive )
	 * <blockquote>Dashboard, Posts, Media, Links, Pages, Comments, Appearance, Plugins, Users, Tools, Settings, Network Admin</blockquote>
	 * @param			string			$sIcon16x16			( optional ) either of the following items.
	 * 	- the URL of the menu icon with the size of 16 by 16 in pixel.
	 *  - the file path of the menu icon with the size of 16 by 16 in pixel.
	 *  - the name of a Dashicons helper class to use a font icon, e.g. <code>dashicons-editor-customchar</code>.
	 *  - the string, 'none', to leave div.wp-menu-image empty so an icon can be added via CSS.
	 *  - a base64-encoded SVG using a data URI, which will be colored to match the color scheme. This should begin with 'data:image/svg+xml;base64,'.
	 * @param			string			$iMenuPosition		( optional ) the position number that is passed to the <var>$position</var> parameter of the <a href="http://codex.wordpress.org/Function_Reference/add_menu_page">add_menu_page()</a> function.
	 * @return			void
	 */
	public function setRootMenuPage( $sRootMenuLabel, $sIcon16x16=null, $iMenuPosition=null ) {

		$sRootMenuLabel = trim( $sRootMenuLabel );
		$sSlug = $this->_isBuiltInMenuItem( $sRootMenuLabel );	// if true, this method returns the slug
		$this->oProp->aRootMenu = array(
			'sTitle'			=> $sRootMenuLabel,
			'sPageSlug' 		=> $sSlug ? $sSlug : $this->oProp->sClassName,	
			'sIcon16x16'		=> $this->oUtil->resolveSRC( $sIcon16x16 ),
			'iPosition'			=> $iMenuPosition,
			'fCreateRoot'		=> $sSlug ? false : true,
		);	
					
	}
		/**
		 * Checks if a menu item is a WordPress built-in menu item from the given menu label.
		 * 
		 * @since			2.0.0
		 * @internal
		 * @return			void|string			Returns the associated slug string, if true.
		 */ 
		private function _isBuiltInMenuItem( $sMenuLabel ) {
			
			$sMenuLabelLower = strtolower( $sMenuLabel );
			if ( array_key_exists( $sMenuLabelLower, self::$_aBuiltInRootMenuSlugs ) )
				return self::$_aBuiltInRootMenuSlugs[ $sMenuLabelLower ];
			
		}	
	
	/**
	 * Sets the top level menu page by page slug.
	 * 
	 * The page should be already created or scheduled to be created separately.
	 * 
	 * <h4>Example</h4>
	 * <code>$this->setRootMenuPageBySlug( 'edit.php?post_type=apf_posts' );</code>
	 * 
	 * @since			2.0.0
	 * @since			3.0.0			The scope was changed to public from protected.
	 * @access			public
	 * @remark			The user may use this method in their extended class definition.
	 * @param			string			$sRootMenuSlug			The page slug of the top-level root page.
	 * @return			void
	 */ 
	public function setRootMenuPageBySlug( $sRootMenuSlug ) {
		
		$this->oProp->aRootMenu['sPageSlug'] = $sRootMenuSlug;	// do not sanitize the slug here because post types includes a question mark.
		$this->oProp->aRootMenu['fCreateRoot'] = false;		// indicates to use an existing menu item. 
		
	}
	
	/**
	* Adds sub-menu items on the left sidebar of the administration panel. 
	* 
	* It supports pages and links. Each of them has the specific array structure.
	* 
	* <h4>Sub-menu Page Array</h4>
	* <ul>
	* <li><strong>title</strong> - ( string ) the page title of the page.</li>
	* <li><strong>page_slug</strong> - ( string ) the page slug of the page. Non-alphabetical characters should not be used including dots(.) and hyphens(-).</li>
	* <li><strong>screen_icon</strong> - ( optional, string ) either the ID selector name from the following list or the icon URL. The size of the icon should be 32 by 32 in pixel.
	*	<pre>edit, post, index, media, upload, link-manager, link, link-category, edit-pages, page, edit-comments, themes, plugins, users, profile, user-edit, tools, admin, options-general, ms-admin, generic</pre>
	*	<p><strong>Notes</strong>: the <em>generic</em> icon is available WordPress version 3.5 or above.</p>
	* </li>
	* <li><strong>sCapability</strong> - ( optional, string ) the access level to the created admin pages defined [here](http://codex.wordpress.org/Roles_and_Capabilities). If not set, the overall capability assigned in the class constructor, which is *manage_options* by default, will be used.</li>
	* <li><strong>order</strong> - ( optional, integer ) the order number of the page. The lager the number is, the lower the position it is placed in the menu.</li>
	* <li><strong>fShowPageHeadingTab</strong> - ( optional, boolean ) if this is set to false, the page title won't be displayed in the page heading tab. Default: true.</li>
	* </ul>
	* <h4>Sub-menu Link Array</h4>
	* <ul>
	* <li><strong>title</strong> - ( string ) the link title.</li>
	* <li><strong>href</strong> - ( string ) the URL of the target link.</li>
	* <li><strong>sCapability</strong> - ( optional, string ) the access level to show the item, defined [here](http://codex.wordpress.org/Roles_and_Capabilities). If not set, the overall capability assigned in the class constructor, which is *manage_options* by default, will be used.</li>
	* <li><strong>order</strong> - ( optional, integer ) the order number of the page. The lager the number is, the lower the position it is placed in the menu.</li>
	* <li><strong>fShowPageHeadingTab</strong> - ( optional, boolean ) if this is set to false, the page title won't be displayed in the page heading tab. Default: true.</li>
	* </ul>
	* 
	* <h4>Example</h4>
	* <code>$this->addSubMenuItems(
	*		array(
	*			'title' => 'Various Form Fields',
	*			'page_slug' => 'first_page',
	*			'screen_icon' => 'options-general',
	*		),
	*		array(
	*			'title' => 'Manage Options',
	*			'page_slug' => 'second_page',
	*			'screen_icon' => 'link-manager',
	*		),
	*		array(
	*			'title' => 'Google',
	*			'href' => 'http://www.google.com',	
	*			'show_page_heading_tab' => false,	// this removes the title from the page heading tabs.
	*		),
	*	);</code>
	* 
	* @since			2.0.0
	* @since			3.0.0			Changed the scope to public.
	* @remark			The sub menu page slug should be unique because add_submenu_page() can add one callback per page slug.
	* @remark			The user may use this method in their extended class definition.
	* @remark			Accepts variadic parameters; the number of accepted parameters are not limited to three.
	* @param			array		$aSubMenuItem1		a first sub-menu array.
	* @param			array		$aSubMenuItem2		( optional ) a second sub-menu array.
	* @param			array		$_and_more				( optional ) third and add items as many as necessary with next parameters.
	* @access 			public
	* @return			void
	*/		
	public function addSubMenuItems( $aSubMenuItem1, $aSubMenuItem2=null, $_and_more=null ) {
		foreach ( func_get_args() as $aSubMenuItem ) 
			$this->addSubMenuItem( $aSubMenuItem );		
	}
	
	/**
	* Adds the given sub-menu item on the left sidebar of the administration panel.
	* 
	* This only adds one single item, called by the above <em>addSubMenuItem()</em> method.
	* 
	* The array structure of the parameter is documented in the <em>addSubMenuItem()</em> method section.
	* 
	* @since			2.0.0
	* @since			3.0.0			Changed the scope to public.
	* @remark			The sub menu page slug should be unique because add_submenu_page() can add one callback per page slug.
	* @remark			The user may use this method.
	* @param			array		$aSubMenuItem			a first sub-menu array.
	* @access 			public
	* @return			void
	*/	
	public function addSubMenuItem( array $aSubMenuItem ) {
		if ( isset( $aSubMenuItem['href'] ) ) 
			$this->addSubMenuLink( $aSubMenuItem );
		else 
			$this->addSubMenuPage( $aSubMenuItem );
	}

	/**
	* Adds the given link into the menu on the left sidebar of the administration panel.
	* 
	* @since			2.0.0
	* @since			3.0.0			Changed the scope to public from protected.
	* @remark			The user may use this method in their extended class definition.
	* @param			string		$sMenuTitle			the menu title.
	* @param			string		$sURL					the URL linked to the menu.
	* @param			string		$sCapability			( optional ) the access level. ( http://codex.wordpress.org/Roles_and_Capabilities)
	* @param			string		$nOrder				( optional ) the order number. The larger it is, the lower the position it gets.
	* @param			string		$bShowPageHeadingTab		( optional ) if set to false, the menu title will not be listed in the tab navigation menu at the top of the page.
	* @access 			public
	* @return			void
	*/	
	public function addSubMenuLink( array $aSubMenuLink ) {
	// public function addSubMenuLink( $sMenuTitle, $sURL, $sCapability=null, $nOrder=null, $bShowPageHeadingTab=true, $bShowInMenu=true ) {
		
		// If required keys are not set, return.
		if ( ! isset( $aSubMenuLink['href'], $aSubMenuLink['title'] ) ) return;
		
		// If the set URL is not valid, return.
		if ( ! filter_var( $aSubMenuLink['href'], FILTER_VALIDATE_URL ) ) return;

		$this->oProp->aPages[ $aSubMenuLink['href'] ] = $this->_formatSubmenuLinkArray( $aSubMenuLink );
			
	}	
	
	/**
	 * Adds sub-menu pages.
	 * 
	 * Use addSubMenuItems() instead, which supports external links.
	 * 
	 * @since			2.0.0
	 * @since			3.0.0			The scope was changed to public from protected.
	 * @internal
	 * @return			void
	 * @remark			The sub menu page slug should be unique because add_submenu_page() can add one callback per page slug.
	 * @remark			The user may use this method.
	 */ 
	public function addSubMenuPages() {
		foreach ( func_get_args() as $aSubMenuPage ) 
			$this->addSubMenuPage( $aSubMenuPage );
	}
	
	/**
	 * Adds a single sub-menu page.
	 * 
	 * <h4>Example</h4>
	 * <code>$this->addSubMenuPage( 'My Page', 'my_page', 'edit-pages' );</code>
	 * 
	 * @access			public
	 * @since			2.0.0
	 * @since			2.1.2			The key name page_heading_tab_visibility was changed to fShowPageHeadingTab
	 * @since			2.1.6			$sScreenIcon accepts a file path.
	 * @since			3.0.0			The scope was changed to public from protected.
	 * @remark			The sub menu page slug should be unique because add_submenu_page() can add one callback per page slug.
	 * @param			string			$sPageTitle			The title of the page.
	 * @param			string			$sPageSlug			The slug of the page.
	 * @param			string			$sScreenIcon			( optional ) Either a screen icon ID, a url of the icon, or a file path to the icon, with the size of 32 by 32 in pixel. The accepted icon IDs are as follows.
	 * <blockquote>edit, post, index, media, upload, link-manager, link, link-category, edit-pages, page, edit-comments, themes, plugins, users, profile, user-edit, tools, admin, options-general, ms-admin, generic</blockquote>
	 * <strong>Note:</strong> the <em>generic</em> ID is available since WordPress 3.5.
	 * @param			string			$sCapability			( optional ) The <a href="http://codex.wordpress.org/Roles_and_Capabilities">access level</a> to the page.
	 * @param			integer			$nOrder				( optional ) the order number of the page. The lager the number is, the lower the position it is placed in the menu.
	 * @param			boolean			$bShowPageHeadingTab	( optional ) If this is set to false, the page title won't be displayed in the page heading tab. Default: true.
	 * @param			boolean			$bShowInMenu			( optional ) If this is set to false, the page title won't be displayed in the sidebar menu while the page is still accessible. Default: true.
	 * @return			void
	 */ 
	public function addSubMenuPage( array $aSubMenuPage ) {
	// public function addSubMenuPage( $sPageTitle, $sPageSlug, $sScreenIcon=null, $sCapability=null, $nOrder=null, $bShowPageHeadingTab=true, $bShowInMenu=true ) {
		
/* 	 $_aStructure_SubMenuPageForUser = array(
		'title' => null, 
		'page_slug' => null, 
		'screen_icon' => null,
		'capability' => null, 
		'order' => null,
		'show_page_heading_tab' => true,	// if this is false, the page title won't be displayed in the page heading tab.
		'show_in_menu' => true,	// if this is false, the menu label will not be displayed in the sidebar menu.
	);
	
	$_aStructure_SubMenuPageForSystem = array(
		'sTitle' => null,
		'sPageSlug' => null,
		'sType' => 'page',
'sIcon32x32' => null,
'sScreenIconID' => null,
'capability' => null, 		
'nOrder' => null,
'fShowPageHeadingTab' => true,
'fShowInMenu' => true,		
'show_page_title'			=> null,			// boolean
'fShowPageHeadingTabs'		=> null,		// boolean
'fShowInPageTabs'			=> null,			// boolean
'sInPageTabTag'				=> null,			// string
'sPageHeadingTabTag'		=> null,		// string			
	);		 */
		if ( ! isset( $aSubMenuPage['page_slug'] ) ) return;
			
		$aSubMenuPage['page_slug'] = $this->oUtil->sanitizeSlug( $aSubMenuPage['page_slug'] );
		$this->oProp->aPages[ $aSubMenuPage['page_slug'] ] = $this->_formatSubMenuPageArray( $aSubMenuPage );

		
	}
					
	/**
	 * Builds the sidebar menu of the added pages.
	 * 
	 * @since			2.0.0
	 */
	public function _replyToBuildMenu() {
		
		// If the root menu label is not set but the slug is set, 
		if ( $this->oProp->aRootMenu['fCreateRoot'] ) 
			$this->_registerRootMenuPage();
		
		// Apply filters to let other scripts add sub menu pages.
		$this->oProp->aPages = $this->oUtil->addAndApplyFilter(		// Parameters: $oCallerObject, $sFilter, $vInput, $vArgs...
			$this,
			"pages_{$this->oProp->sClassName}", 
			$this->oProp->aPages
		);
		
		// Sort the page array.
		uasort( $this->oProp->aPages, array( $this, '_sortByOrder' ) ); 
		
		// Set the default page, the first element.
		foreach ( $this->oProp->aPages as $aPage ) {
			
			if ( ! isset( $aPage['page_slug'] ) ) continue;
			$this->oProp->sDefaultPageSlug = $aPage['page_slug'];
			break;
			
		}
		
		// Register them.
		foreach ( $this->oProp->aPages as &$aSubMenuItem ) {
			$aSubMenuItem = $this->_formatSubMenuItemArray( $aSubMenuItem );	// needs to be sanitized because there are hook filters applied to this array.
			$this->_registerSubMenuItem( $aSubMenuItem );
		}
						
		// After adding the sub menus, if the root menu is created, remove the page that is automatically created when registering the root menu.
		if ( $this->oProp->aRootMenu['fCreateRoot'] ) 
			remove_submenu_page( $this->oProp->aRootMenu['sPageSlug'], $this->oProp->aRootMenu['sPageSlug'] );
		
	}	
		
		/**
		 * Registers the root menu page.
		 * 
		 * @since			2.0.0
		 */ 
		private function _registerRootMenuPage() {
			$sHookName = add_menu_page(  
				$this->oProp->sClassName,						// Page title - will be invisible anyway
				$this->oProp->aRootMenu['sTitle'],				// Menu title - should be the root page title.
				$this->oProp->sCapability,						// Capability - access right
				$this->oProp->aRootMenu['sPageSlug'],			// Menu ID 
				'', //array( $this, $this->oProp->sClassName ), 	// Page content displaying function
				$this->oProp->aRootMenu['sIcon16x16'],		// icon path
				isset( $this->oProp->aRootMenu['iPosition'] ) ? $this->oProp->aRootMenu['iPosition'] : null	// menu position
			);
		}
		
		/**
		 * Formats the sub-menu item arrays.
		 * @since			3.0.0
		 */
		private function _formatSubMenuItemArray( $aSubMenuItem ) {
			
			if ( isset( $aSubMenuItem['page_slug'] ) )
				return $this->_formatSubMenuPageArray( $aSubMenuItem );
				
			if ( isset( $aSubMenuItem['href'] ) )
				return $this->_formatSubmenuLinkArray( $aSubMenuItem ); 
				
			return array();
			
		}
		
		/**
		 * Formats the given sub-menu link array.
		 * @since			3.0.0
		 */
		private function _formatSubmenuLinkArray( $aSubMenuLink ) {
			
			// If the set URL is not valid, return.
			if ( ! filter_var( $aSubMenuLink['href'], FILTER_VALIDATE_URL ) ) return array();
			
			return $this->oUtil->uniteArrays(		
				array(  
					'capability'	=> isset( $aSubMenuLink['capability'] ) ? $aSubMenuLink['capability'] : $this->oProp->sCapability,
					'order'			=> isset( $aSubMenuLink['order'] ) && is_numeric( $aSubMenuLink['order'] ) ? $aSubMenuLink['order'] : count( $this->oProp->aPages ) + 10,
				),
				$aSubMenuLink + self::$_aStructure_SubMenuLinkForUser
			);			
			
		}
		/**
		 * Formats the given sub-menu page array.
		 * @since			3.0.0
		 */
		private function _formatSubMenuPageArray( $aSubMenuPage ) {
			
			$aSubMenuPage = $aSubMenuPage + self::$_aStructure_SubMenuPageForUser;

			$aSubMenuPage['screen_icon_id'] = trim( $aSubMenuPage['screen_icon_id'] );			
			return $this->oUtil->uniteArrays(
				array( 
					'href_icon_32x32'			=> $this->oUtil->resolveSRC( $aSubMenuPage['screen_icon'], true ),
					'screen_icon_id'			=> in_array( $aSubMenuPage['screen_icon'], self::$_aScreenIconIDs ) ? $aSubMenuPage['screen_icon'] : 'generic',		// $_aScreenIconIDs is defined in the page class.
					'capability'				=> isset( $aSubMenuPage['capability'] ) ? $aSubMenuPage['capability'] : $this->oProp->sCapability,
					'order'						=> is_numeric( $aSubMenuPage['order'] ) ? $aSubMenuPage['order'] : count( $this->oProp->aPages ) + 10,
				),
				$aSubMenuPage,
				array(
					'show_page_title'			=> $this->oProp->bShowPageTitle,			// boolean
					'show_page_heading_tabs'	=> $this->oProp->bShowPageHeadingTabs,		// boolean
					'show_in_page_tabs'			=> $this->oProp->bShowInPageTabs,			// boolean
					'in_page_tab_tag'			=> $this->oProp->sInPageTabTag,				// string
					'page_heading_tab_tag'		=> $this->oProp->sPageHeadingTabTag,		// string
				)
			);			
			
		}
		
		/**
		 * Registers the sub-menu item.
		 * 
		 * @since			2.0.0
		 * @since			3.0.0			Changed the name from registerSubMenuPage().
		 * @remark			Used in the buildMenu() method. 
		 * @remark			Within the <em>admin_menu</em> hook callback process.
		 * @remark			The sub menu page slug should be unique because add_submenu_page() can add one callback per page slug.
		 */ 
		private function _registerSubMenuItem( $aArgs ) {
				
			// Variables
			$sType = $aArgs['type'];	// page or link
			$sTitle = $sType == 'page' ? $aArgs['title'] : $aArgs['title'];
			$sCapability = isset( $aArgs['capability'] ) ? $aArgs['capability'] : $this->oProp->sCapability;
				
			// Check the capability
			if ( ! current_user_can( $sCapability ) ) return;		
			
			// Add the sub-page to the sub-menu
			$aResult = array();
			$sRootPageSlug = $this->oProp->aRootMenu['sPageSlug'];
			$sMenuLabel = plugin_basename( $sRootPageSlug );	// Make it compatible with the add_submenu_page() function.
			
			// If it's a page - it's possible that the page_slug key is not set if the user uses a method like setPageHeadingTabsVisibility() prior to addSubMenuItam().
			if ( $sType == 'page' && isset( $aArgs['page_slug'] ) ) {		
				
				$sPageSlug = $aArgs['page_slug'];
				$aResult[ $sPageSlug ] = add_submenu_page( 
					$sRootPageSlug,						// the root(parent) page slug
					$sTitle,								// page_title
					$sTitle,								// menu_title
					$sCapability,				 			// capability
					$sPageSlug,	// menu_slug
					// In admin.php ( line 149 of WordPress v3.6.1 ), do_action($page_hook) ( where $page_hook is $aResult[ $sPageSlug ] )
					// will be executed and it triggers the __call magic method with the method name of "md5 class hash + _page_ + this page slug".
					array( $this, $this->oProp->sClassHash . '_page_' . $sPageSlug )
				);			
				
				add_action( "load-" . $aResult[ $sPageSlug ] , array( $this, "load_pre_" . $sPageSlug ) );
					
				// If the visibility option is false, remove the one just added from the sub-menu array
				if ( ! $aArgs['show_in_menu'] ) {

					foreach( ( array ) $GLOBALS['submenu'][ $sMenuLabel ] as $iIndex => $aSubMenu ) {
						
						if ( ! isset( $aSubMenu[ 3 ] ) ) continue;
						
						// the array structure is defined in plugin.php - $submenu[$parent_slug][] = array ( $menu_title, $capability, $menu_slug, $page_title ) 
						if ( $aSubMenu[0] == $sTitle && $aSubMenu[3] == $sTitle && $aSubMenu[2] == $sPageSlug ) {
							unset( $GLOBALS['submenu'][ $sMenuLabel ][ $iIndex ] );
							
							// The page title in the browser window title bar will miss the page title as this is left as it is.
							$this->oProp->aHiddenPages[ $sPageSlug ] = $sTitle;
							add_filter( 'admin_title', array( $this, '_replyToFixPageTitleForHiddenPages' ), 10, 2 );
							
							break;
						}
					}
				} 
					
			} 
			// If it's a link,
			if ( $sType == 'link' && $aArgs['show_in_menu'] ) {
				
				if ( ! isset( $GLOBALS['submenu'][ $sMenuLabel ] ) )
					$GLOBALS['submenu'][ $sMenuLabel ] = array();
				
				$GLOBALS['submenu'][ $sMenuLabel ][] = array ( 
					$sTitle, 
					$sCapability, 
					$aArgs['href'],
				);	
			}
		
			return $aResult;	// maybe useful to debug.

		}		
		
		/**
		 * A callback function for the admin_title filter to fix the page title for hidden pages.
		 * @since			2.1.4
		 */
		public function _replyToFixPageTitleForHiddenPages( $sAdminTitle, $sPageTitle ) {

			if ( isset( $_GET['page'], $this->oProp->aHiddenPages[ $_GET['page'] ] ) )
				return $this->oProp->aHiddenPages[ $_GET['page'] ] . $sAdminTitle;
				
			return $sAdminTitle;
			
		}		
}
endif;