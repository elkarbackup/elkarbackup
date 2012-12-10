<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Menu;

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
                $newParent = $parent->addChild($itemDescription['label'],
                                               array('route'           => $itemDescription['route'],
                                                     'routeParameters' => $itemDescription['routeParameters'],
                                                     'attributes'      => array("data-dojo-type"  => "dijit/PopupMenuItem",
                                                                                "data-dojo-props" => $this->generateOnClickHandler($itemDescription['route'], $itemDescription['routeParameters']))));
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
            $menuBarItem = $menuBar->addChild($itemDescription['label'],
                                              array('attributes'         => array('data-dojo-type' => 'dijit/PopupMenuBarItem'),
                                                    'childrenAttributes' => array('data-dojo-type' => 'dijit/DropDownMenu')));
            if (is_array($itemDescription['children'])) {
                $this->generateMenu($menuBarItem, $itemDescription['children']);
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
        $menu = array(array('label'    => $t->trans('Home', array(), 'BinovoTknikaBackups'),
                            'children' => array(array('label'    => $t->trans('Show home page', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'home'))),
                      array('label'    => $t->trans('Clients', array(), 'BinovoTknikaBackups'),
                            'children' => array(array('label'    => $t->trans('Show', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'showClients'),
                                                array('label'    => $t->trans('Add', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'editClient',
                                                      'routeParameters' => array('id' => 'new')))),
                      array('label'    => $t->trans('Policies', array(), 'BinovoTknikaBackups'),
                            'children' => array(array('label'    => $t->trans('Show', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'showPolicies'),
                                                array('label'    => $t->trans('Add', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'editPolicy',
                                                      'routeParameters' => array('id' => 'new')))),
                      array('label'    => $t->trans('Users', array(), 'BinovoTknikaBackups'),
                            'children' => array(array('label'    => $t->trans('Logout', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'logout'),
                                                array('label'    => $t->trans('Change password', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'changePassword'),
                                                array('label'    => $t->trans('Show', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'showUsers'))),
                      array('label'    => $t->trans('Config', array(), 'BinovoTknikaBackups'),
                            'children' => array(array('label'    => $t->trans('Manage parameters', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'manageParameters'),
                                                array('label'    => $t->trans('Repository backups script', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'getRepositoryBackupScript'))),
                      array('label'    => $t->trans('Logs', array(), 'BinovoTknikaBackups'),
                            'children' => array(array('label'    => $t->trans('Show Logs', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'showLogs'))),
                      array('label'    => $t->trans('Help', array(), 'BinovoTknikaBackups'),
                            'children' => array(array('label'    => $t->trans('About', array(), 'BinovoTknikaBackups'),
                                                      'route'    => 'about'))),

            );

        return $this->generateMenuBar($factory, $menu);
    }
}