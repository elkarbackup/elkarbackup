/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */
require(['dojo', 'dojo/parser', 'dijit/form/Button', 'dijit/MenuBar', 'dijit/MenuBarItem', 'dijit/PopupMenuBarItem', 'dijit/DropDownMenu', 'dijit/MenuItem', 'dojo/NodeList-manipulate', 'dojo/ready', 'dojo/dom-class', 'dojo/query!css2', 'dijit/registry'],
function(dojo, parser, Button, MenuBar, MenuBarItem, PopuMenuBarItem, DropDownMenu, MenuItem, NodeList, ready, domClass, query, registry) {
    ready(function() {
              parser.parse().then(function(){
                // Add icons to the menubar
			          var menuBar = registry.byId('dijit_MenuBar_0');
  				      if (menuBar){
                    lastItem = query('[role="menuitem"]:last-child')[0]; // Session
                    lastItem2 = query('[role="menuitem"]:nth-last-child(2)')[0]; // Config
                    dojo.setAttr(lastItem, 'innerHTML','');
    			          dojo.setAttr(lastItem2, 'innerHTML','');
                    domClass.add(lastItem, 'glyphicon glyphicon-log-out');
                    domClass.add(lastItem2, 'glyphicon glyphicon-cog');
                }

                dojo.destroy('menuplaceholder');
                dojo.setAttr(dojo.byId('menu'), 'style', '');
               });
          });
});
