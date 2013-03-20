/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */
require(['dojo', 'dojo/parser', 'dijit/form/Button', 'dijit/MenuBar', 'dijit/MenuBarItem', 'dijit/PopupMenuBarItem', 'dijit/DropDownMenu', 'dijit/MenuItem', 'dojo/NodeList-manipulate', 'dojo/ready'],
function(dojo, parser, Button, MenuBar, MenuBarItem, PopuMenuBarItem, DropDownMenu, MenuItem, NodeList, ready) {
    ready(function() {
              parser.parse().then(function(){
                                      dojo.destroy('menuplaceholder');
                                      dojo.setAttr(dojo.byId('menu'), 'style', '');
                                  });
          });
});
