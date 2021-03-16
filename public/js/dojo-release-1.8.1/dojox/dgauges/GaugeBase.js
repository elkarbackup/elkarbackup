//>>built
define("dojox/dgauges/GaugeBase",["dojo/_base/lang","dojo/_base/declare","dojo/dom-geometry","dijit/registry","dijit/_WidgetBase","dojo/_base/html","dojo/_base/event","dojox/gfx","dojox/widget/_Invalidating","./ScaleBase","dojox/gfx/matrix"],function(_1,_2,_3,_4,_5,_6,_7,_8,_9,_a,_b){
return _2("dojox.dgauges.GaugeBase",[_5,_9],{_elements:null,_scales:null,_elementsIndex:null,_elementsRenderers:null,_gfxGroup:null,_mouseShield:null,_widgetBox:null,_node:null,value:0,font:null,constructor:function(_c,_d){
this.font={family:"Helvetica",style:"normal",variant:"small-caps",weight:"bold",size:"10pt",color:"black"};
this._elements=[];
this._scales=[];
this._elementsIndex={};
this._elementsRenderers={};
this._node=_4.byId(_d);
var _e=_6.getMarginBox(_d);
this.surface=_8.createSurface(this._node,_e.w||1,_e.h||1);
this._widgetBox=_e;
this._baseGroup=this.surface.createGroup();
this._mouseShield=this._baseGroup.createGroup();
this._gfxGroup=this._baseGroup.createGroup();
},_setCursor:function(_f){
if(this._node){
this._node.style.cursor=_f;
}
},_computeBoundingBox:function(_10){
return _10?_10.getBoundingBox():{x:0,y:0,width:0,height:0};
},destroy:function(){
this.surface.destroy();
this.inherited(arguments);
},resize:function(_11,_12){
var box;
switch(arguments.length){
case 1:
box=_1.mixin({},_11);
_3.setMarginBox(this._node,box);
break;
case 2:
box={w:_11,h:_12};
_3.setMarginBox(this._node,box);
break;
}
box=_3.getMarginBox(this._node);
this._widgetBox=box;
var d=this.surface.getDimensions();
if(d.width!=box.w||d.height!=box.h){
this.surface.setDimensions(box.w,box.h);
this._mouseShield.clear();
this._mouseShield.createRect({x:0,y:0,width:box.w,height:box.h}).setFill([0,0,0,0]);
return this.invalidateRendering();
}else{
return this;
}
},addElement:function(_13,_14){
if(this._elementsIndex[_13]&&this._elementsIndex[_13]!=_14){
this.removeElement(_13);
}
if(_1.isFunction(_14)){
var _15={};
_1.mixin(_15,new _9());
_15._name=_13;
_15._gfxGroup=this._gfxGroup.createGroup();
_15.width=0;
_15.height=0;
_15._isGFX=true;
_15.refreshRendering=function(){
_15._gfxGroup.clear();
return _14(_15._gfxGroup,_15.width,_15.height);
};
this._elements.push(_15);
this._elementsIndex[_13]=_15;
}else{
_14._name=_13;
_14._gfxGroup=this._gfxGroup.createGroup();
_14._gauge=this;
this._elements.push(_14);
this._elementsIndex[_13]=_14;
if(_14 instanceof _a){
this._scales.push(_14);
}
}
return this.invalidateRendering();
},removeElement:function(_16){
var _17=this._elementsIndex[_16];
if(_17){
_17._gfxGroup.removeShape();
var idx=this._elements.indexOf(_17);
this._elements.splice(idx,1);
if(_17 instanceof _a){
var _18=this._scales.indexOf(_17);
this._scales.splice(_18,1);
}
delete this._elementsIndex[_16];
delete this._elementsRenderers[_16];
}
this.invalidateRendering();
return _17;
},getElement:function(_19){
return this._elementsIndex[_19];
},getElementRenderer:function(_1a){
return this._elementsRenderers[_1a];
},onStartEditing:function(_1b){
},onEndEditing:function(_1c){
}});
});
