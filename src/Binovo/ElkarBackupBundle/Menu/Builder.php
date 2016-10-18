<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
    /**
     * Generates the appropiate onClick handler for the dijit/MenuItem leaf menu items.
     *
     * @param  string   $controller The name of the controller to which this menu item should link
     *
     * @param  array    $params     The parameters to build the query string. Optional.
     *
     * @param  boolean  $absolute   Whether to generate an absolute URL or not. Optional.
     *
     * @return string   Something like "onClick: function(){document.location.href='/home';}"
     *
     */
    protected function generateOnClickHandler($controller, array $params = array(), $absolute = false)
    {
        $router = $this->container->get('router');
        $path = $router->generate($controller, $params, $absolute);
        return "onClick: function(){document.location.href='$path';}";
    }

    /**
     * Recursively generates a menu from the description.
     *
     * Helper function called from generateMenuBar.
     *
     * @param  object   $parent      The parent menu or menu bar.
     *
     * @param  array    $description See generateMenuBar's $description parameter.
     *
     */
    protected function generateMenu($parent, array $description)
    {
        foreach ($description as $itemDescription) {
            if (empty($itemDescription['routeParameters'])) {
                $itemDescription['routeParameters'] = array();
            }
            if (empty($itemDescription['children'])) {
                // submenus hijos
                $parent->addChild($itemDescription['label'],
                                  array('route'            => $itemDescription['route'],
                                        'routeParameters'  => $itemDescription['routeParameters'],
                                        'extras'           => array('safe_label' => true),
                                        'label'            => $itemDescription['icon'],
                                        'attributes'       => array('class' => $itemDescription['class'])));
            }
        }
    }

    /**
     * Recursively generates a menu bar from the description.
     *
     * The description is an array of assocs. Each assoc must have a
     * label attribute and a route attribute or a children
     * attribute. The label attribute will be the menu items label,
     * the route attribute will be used to create the link. The
     * children attribute is again the same structure used for
     * submenus.
     *
     * @param  FactoryInterface   $factory
     *
     * @param  array              $description Menu's description.
     *
     * @return object             The menu bar.
     *
     */
    protected function generateMenuBar(FactoryInterface $factory, array $description)
    {
        $menuBar = $factory->createItem('root');
    	  $menuBar->setChildrenAttribute('class', 'nav navbar-nav');

        foreach ($description as $itemDescription) {
            if (isset($itemDescription['children']) && is_array($itemDescription['children'])) {
                $menuBarItem = $menuBar->addChild($itemDescription['label'].'_withsubm',
                                                  array('extras'           => array('safe_label' => true),
                                                        'label'            => $itemDescription['icon'],
                                                        'attributes'       => array('class' => $itemDescription['class']),
                                                        'childrenAttributes' => array('class' => 'dropdown-menu sub-menu')));
                $this->generateMenu($menuBarItem, $itemDescription['children']);
            } else {
                if (empty($itemDescription['routeParameters'])) {
                    $itemDescription['routeParameters'] = array();
                }
                $menuBar->addChild($itemDescription['label'],
                                   array('route'            => $itemDescription['route'],
                                         'routeParameters'  => $itemDescription['routeParameters'],
                                         'extras'           => array('safe_label' => true),
                                         'label'            => $itemDescription['icon'],
                                         'attributes'       => array('class' => $itemDescription['class'])));
            }
        }
        return $menuBar;
    }

    /**
     * Returns the main menu.
     *
     * Call from a template using the knp_menu_render function.
     *
     */
    public function mainMenu(FactoryInterface $factory, array $options)
    {

	$doctrine = $this->container->get('doctrine');
		$em = $doctrine->getManager();
	if($this->container->get('security.context')->isGranted('ROLE_ADMIN')){

        $t = $this->container->get('translator');
        $menu = array(array('label'    => $t->trans('Jobs', array(), 'BinovoElkarBackup'),
                            'route'    => 'showClients',
                            'class'    => 'Clients',
                            'icon'     => '<i></i><span>'.$t->trans('Jobs', array(), 'BinovoElkarBackup').'</span></a>'),
                      array('label'    => $t->trans('Policies', array(), 'BinovoElkarBackup'),
                            'route'    => 'showPolicies',
                            'class'    => 'Policies',
                            'icon'     => '<i></i><span>'.$t->trans('Policies', array(), 'BinovoElkarBackup').'</span></a>'),
                      array('label'    => $t->trans('Scripts', array(), 'BinovoElkarBackup'),
                            'route'    => 'showScripts',
                            'class'    => 'Scripts',
                            'icon'     => '<i></i><span>'.$t->trans('Scripts', array(), 'BinovoElkarBackup').'</span></a>'),
                      array('label'    => $t->trans('Users', array(), 'BinovoElkarBackup'),
                            'route'    => 'showUsers',
                            'class'    => 'Users',
                            'icon'     => '<i></i><span>'.$t->trans('Users', array(), 'BinovoElkarBackup').'</span></a>'),
                      array('label'    => $t->trans('Logs', array(), 'BinovoElkarBackup'),
                            'route'    => 'showLogs',
                            'class'    => 'Logs',
                            'icon'     => '<i></i><span>'.$t->trans('Logs', array(), 'BinovoElkarBackup').'</span></a>'),

                      array('label'     => $t->trans('Config', array(), 'BinovoElkarBackup'),
                            'class'    => 'Config dropdown dropdown-toggle',
                            'icon'     => '<i class="glyphicon glyphicon-cog"></i><span></span></a>',
                            'children' => array(array('label'    => $t->trans('Preferences', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'managePreferences',
                                                      'class'    => 'Preferences',
                                                      'icon'     => '<i class="glyphicon glyphicon-wrench"></i><span>'.$t->trans('Preferences', array(), 'BinovoElkarBackup').'</span></a>'),

                                                array('label'    => $t->trans('Change password', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'changePassword',
                                                      'class'    => 'changePassword',
                                                      'icon'     => '<i class="glyphicon glyphicon-lock"></i><span>'.$t->trans('Change password', array(), 'BinovoElkarBackup').'</span></a>'),

                                                array('label'    => $t->trans('Manage parameters', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'manageParameters',
                                                      'class'    => 'manageParameters',
                                                      'icon'     => '<i class="glyphicon glyphicon-list"></i><span>'.$t->trans('Manage parameters', array(), 'BinovoElkarBackup').'</span></a>'),

                                                array('label'    => $t->trans('Manage backups location', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'manageBackupsLocation',
                                                      'class'    => 'manageBackupsLocation',
                                                      'icon'     => '<i class="glyphicon glyphicon-hdd"></i><span>'.$t->trans('Manage backups location', array(), 'BinovoElkarBackup').'</span></a>'),

                                                array('label'    => $t->trans('Repository backup script', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'configureRepositoryBackupScript',
                                                      'class'    => 'configureRepositoryBackupScript',
                                                      'icon'     => '<i class="glyphicon glyphicon-duplicate"></i><span>'.$t->trans('Repository backup script', array(), 'BinovoElkarBackup').'</span></a>'))),

                      array('label'     => $t->trans('Logout', array(), 'BinovoElkarBackup'),
                            'route'     => 'logout',
                            'class'     => 'logout',
                            'icon'      => '<i class="glyphicon glyphicon-log-out"></i><span></span></a>')


            );
        if ($this->container->get('Tahoe')->isInstalled()) {
            $menu[4]['children'][] = array('label'    => $t->trans('Manage Tahoe storage', array(), 'BinovoElkarTahoe'),
                                                      'route'    => 'tahoeConfig');
        	}
	} else {

		$t = $this->container->get('translator');
        $menu = array(array('label'    => $t->trans('Jobs', array(), 'BinovoElkarBackup'),
                            'route'    => 'showClients',
                            'class'    => 'Clients',
                            'icon'     => '<i></i><span>'.$t->trans('Jobs', array(), 'BinovoElkarBackup').'</span></a>'),
                      array('label'    => $t->trans('Policies', array(), 'BinovoElkarBackup'),
                            'route'    => 'showPolicies',
                            'class'    => 'Policies',
                            'icon'     => '<i></i><span>'.$t->trans('Policies', array(), 'BinovoElkarBackup').'</span></a>'),
                      array('label'    => $t->trans('Scripts', array(), 'BinovoElkarBackup'),
                            'route'    => 'showScripts',
                            'class'    => 'Scripts',
                            'icon'     => '<i"></i><span>'.$t->trans('Scripts', array(), 'BinovoElkarBackup').'</span></a>'),

                      array('label'    => $t->trans('Logs', array(), 'BinovoElkarBackup'),
                            'route'    => 'showLogs',
                            'class'    => 'Logs',
                            'icon'     => '<i></i><span>'.$t->trans('Logs', array(), 'BinovoElkarBackup').'</span></a>'),

                            array('label'     => $t->trans('Config', array(), 'BinovoElkarBackup'),
                                  'class'    => 'Config dropdown dropdown-toggle',
                                  'icon'     => '<i class="glyphicon glyphicon-cog"></i><span></span></a>',
                                  'children' => array(array('label'    => $t->trans('Preferences', array(), 'BinovoElkarBackup'),
                                                            'route'    => 'managePreferences',
                                                            'class'    => 'Preferences',
                                                            'icon'     => '<i class="glyphicon glyphicon-wrench"></i><span>'.$t->trans('Preferences', array(), 'BinovoElkarBackup').'</span></a>'),

                                                      array('label'    => $t->trans('Change password', array(), 'BinovoElkarBackup'),
                                                            'route'    => 'changePassword',
                                                            'class'    => 'changePassword',
                                                            'icon'     => '<i class="glyphicon glyphicon-lock"></i><span>'.$t->trans('Change password', array(), 'BinovoElkarBackup').'</span></a>')
                                                )),


                      array('label'     => $t->trans('Logout', array(), 'BinovoElkarBackup'),
                            'route'     => 'logout',
                            'class'     => 'logout',
                            'icon'      => '<i class="glyphicon glyphicon-log-out"></i><span></span></a>')

            );

	 }
        return $this->generateMenuBar($factory, $menu);
    }

    private function getLanguageMenuEntries()
    {
        $t = $this->container->get('translator');
        $locales = $this->container->getParameter('supported_locales');
        $menus = array();
        foreach ($locales as $locale) {
            $menus[] = array('label'           => $t->trans("language_$locale", array(), 'BinovoElkarBackup'),
                             'route'           => 'setLocale',
                             'routeParameters' => array('locale' => $locale));
        }
        return $menus;
    }
}
