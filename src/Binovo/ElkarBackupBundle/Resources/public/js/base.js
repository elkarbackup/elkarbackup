/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */
require(['dojo', 'dojo/parser', 'dijit/form/Button', 'dijit/MenuBar', 'dijit/MenuBarItem', 'dijit/PopupMenuBarItem', 'dijit/DropDownMenu', 'dijit/MenuItem', 'dojo/NodeList-manipulate', 'dojo/ready', 'dojo/dom-class', 'dojo/query!css2'],
function(dojo, parser, Button, MenuBar, MenuBarItem, PopuMenuBarItem, DropDownMenu, MenuItem, NodeList, ready, domClass, query) {
    ready(function() {
              parser.parse().then(function(){
                                      dojo.destroy('menuplaceholder');
                                      dojo.setAttr(dojo.byId('menu'), 'style', '');
                                 });

              lastItem = query('[role="menuitem"]:last-child')[0];
              lastItem2 = query('[role="menuitem"]:nth-last-child(2)')[0];
              dojo.setAttr(lastItem, 'innerHTML','');
              dojo.setAttr(lastItem2, 'innerHTML','');
	      domClass.add(lastItem, 'glyphicon glyphicon-log-out');
              domClass.add(lastItem2, 'glyphicon glyphicon-cog'); 
          });
});
