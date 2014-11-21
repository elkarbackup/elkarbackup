/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */
require(['dojo', 'dojo/dom-construct', 'dojo/fx', 'dojo/ready'],
function(dojo, domConstruct, fx, ready) {
    var iNewKey = 0, newNode, animation, node;
    function publicKeysClick(e) {
        switch (dojo.attr(e.target, 'data-command')) {
        case 'delete-key':
            node = e.target.parentNode.parentNode;
            animation = [fx.wipeOut({node: node})];
            if (1 == dojo.query('#public-keys input[data-command=delete-key]').length) { // deleting last key
                animation.push(fx.wipeIn({node: 'no-keys-defined'}));
            }
            animation = fx.combine(animation);
            dojo.connect(animation, 'onEnd', function(){domConstruct.destroy(node);});
            animation.play();
            break;
        case 'add-key':
            newNode = dojo.place(dojo.string.trim(dojo.byId('prototype').innerHTML.replace(/__name__/g, 'n_' + iNewKey)), dojo.byId('add-key'), 'before');
	              animation = [fx.wipeIn({node: newNode})];
            if (1 == dojo.query('#public-keys input[data-command=delete-key]').length) { // adding first key
                animation.push(fx.wipeOut({node: 'no-keys-defined'}));
            }
            fx.combine(animation).play();
            ++iNewKey;
            break;
        default:
            // nothing to do here
        }
    }
    function init() {
        dojo.connect(dojo.byId('public-keys'), 'onclick', publicKeysClick);
    }
    ready(function(){
              init();
          });
});
