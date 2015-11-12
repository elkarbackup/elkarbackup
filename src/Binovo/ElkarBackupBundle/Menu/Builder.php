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
                $parent->addChild($itemDescription['label'],
                                  array('route'           => $itemDescription['route'],
                                        'routeParameters' => $itemDescription['routeParameters'],
                                        'attributes'      => array("data-dojo-type"  => "dijit/MenuItem",
                                                                   "data-dojo-props" => $this->generateOnClickHandler($itemDescription['route'], $itemDescription['routeParameters']))));
            } else {
                if (isset($itemDescription['route'])) {
                    $newParent = $parent->addChild($itemDescription['label'],
                                                   array('route'           => $itemDescription['route'],
                                                         'routeParameters' => $itemDescription['routeParameters'],
                                                         'attributes'      => array("data-dojo-type"  => "dijit/PopupMenuItem",
                                                                                    "data-dojo-props" => $this->generateOnClickHandler($itemDescription['route'], $itemDescription['routeParameters'])),
                                                         'childrenAttributes' => array('data-dojo-type' => 'dijit/DropDownMenu')));
                } else {
                    $newParent = $parent->addChild($itemDescription['label'],
                                                   array('attributes'         => array("data-dojo-type"  => "dijit/PopupMenuItem"),
                                                         'childrenAttributes' => array('data-dojo-type' => 'dijit/DropDownMenu')));
                }
                $this->generateMenu($newParent, $itemDescription['children']);
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
        $menuBar = $factory->createItem('root', array('childrenAttributes' => array("data-dojo-type" => "dijit/MenuBar")));
        foreach ($description as $itemDescription) {
            if (is_array($itemDescription['children'])) {
                $menuBarItem = $menuBar->addChild($itemDescription['label'],
                                                  array('attributes'         => array('data-dojo-type' => 'dijit/PopupMenuBarItem'),
                                                        'childrenAttributes' => array('data-dojo-type' => 'dijit/DropDownMenu')));
                $this->generateMenu($menuBarItem, $itemDescription['children']);
            } else {
                if (empty($itemDescription['routeParameters'])) {
                    $itemDescription['routeParameters'] = array();
                }
                $menuBar->addChild($itemDescription['label'],
                                   array('route' => $itemDescription['route'],
                                         'routeParameters' => $itemDescription['routeParameters'],
                                         'attributes'      => array("data-dojo-type"  => "dijit/MenuItem",
                                                                    "data-dojo-props" => $this->generateOnClickHandler($itemDescription['route'], $itemDescription['routeParameters']))));

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
        $t = $this->container->get('translator');

        if ($this->container->get('Tahoe')->isInstalled()) {
            $configChildren = array(array('label'    => $t->trans('Manage parameters', array(), 'BinovoElkarBackup'),
                                                  'route'    => 'manageParameters'),
                                            array('label'    => $t->trans('Manage backups location', array(), 'BinovoElkarBackup'),
                                                  'route'    => 'manageBackupsLocation'),
                                            array('label'    => $t->trans('Manage Tahoe storage', array(), 'BinovoElkarTahoe'),
                                                  'route'    => 'tahoeConfig'),
                                            array('label'    => $t->trans('Repository backup script', array(), 'BinovoElkarBackup'),
                                                  'route'    => 'configureRepositoryBackupScript'));
        } else {
            $configChildren = array(array('label'    => $t->trans('Manage parameters', array(), 'BinovoElkarBackup'),
                                                  'route'    => 'manageParameters'),
                                            array('label'    => $t->trans('Manage backups location', array(), 'BinovoElkarBackup'),
                                                  'route'    => 'manageBackupsLocation'),
                                            array('label'    => $t->trans('Repository backup script', array(), 'BinovoElkarBackup'),
                                                  'route'    => 'configureRepositoryBackupScript'));
        }

        $menu = array(array('label'    => $t->trans('Jobs', array(), 'BinovoElkarBackup'),
                            'children' => array(array('label'    => $t->trans('Show', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'showClients'),
                                                array('label'    => $t->trans('Add client', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'editClient',
                                                      'routeParameters' => array('id' => 'new')),
                                                array('label'    => $t->trans('Sort jobs', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'sortJobs'))),
                      array('label'    => $t->trans('Policies', array(), 'BinovoElkarBackup'),
                            'children' => array(array('label'    => $t->trans('Show', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'showPolicies'),
                                                array('label'    => $t->trans('Add', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'editPolicy',
                                                      'routeParameters' => array('id' => 'new')))),
                      array('label'    => $t->trans('Scripts', array(), 'BinovoElkarBackup'),
                            'children' => array(array('label'    => $t->trans('Show', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'showScripts'),
                                                array('label'    => $t->trans('Add', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'editScript',
                                                      'routeParameters' => array('id' => 'new')))),
                      array('label'    => $t->trans('Users', array(), 'BinovoElkarBackup'),
                            'children' => array(array('label'    => $t->trans('Show', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'showUsers'),
                                                array('label'    => $t->trans('Change password', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'changePassword'))),
                      array('label'    => $t->trans('Config', array(), 'BinovoElkarBackup'),
                            'children' => $configChildren ),
                      array('label'    => $t->trans('Logs', array(), 'BinovoElkarBackup'),
                            'children' => array(array('label'    => $t->trans('Show Logs', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'showLogs'))),
                      array('label'    => $t->trans('Session', array(), 'BinovoElkarBackup'),
                            'children' => array(array('label'    => $t->trans('Logout', array(), 'BinovoElkarBackup'),
                                                      'route'    => 'logout'),
                                                array('label'    => $t->trans('Language', array(), 'BinovoElkarBackup'),
                                                      'children' => $this->getLanguageMenuEntries() )))

            );

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
