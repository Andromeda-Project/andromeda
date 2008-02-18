
var x4http=false;var x4httpData=false;var x4httpAfterHandler=false;var x4httpAfterObject=false;var x4httpAfterMethod=false;function x4Boot(){if(navigator.appName=="Microsoft Internet Explorer")
x4http=new ActiveXObject("Microsoft.XMLHTTP");else
x4http=new XMLHttpRequest();x4.addEventListener(document,'keypress',x4.bodyKeyPress);x4.ajax('x4xMenu=1',null,x4Menu,'init');}
var x4={data:[],messages:[],ajax:function(getString,pHandler,pObj,pMethod){x4httpData=false;x4httpAfterHandler=pHandler==null?false:pHandler;x4httpAfterObject=pObj==null?false:pObj;x4httpAfterMethod=pMethod==null?false:pMethod;x4http.open('get','x4index.php?'+getString);x4http.onreadystatechange=x4.ajaxResponseHandler;x4http.send(null);},ajaxErrors:function(){if(typeof(x4httpData.message)=='undefined'){return false;}
if(typeof(x4httpData.message.error)=='undefined'){return false;}
return x4httpData.message.error;},ajaxResponseHandler:function(){if(x4http.readyState!=4)return;var dataIsOK=false;try{eval('x4httpData = ('+x4http.responseText+')');dataIsOK=true;}
catch(e){alert('Could not process server response!');}
if(typeof(x4httpData.message)!='undefined'){x4.messages=x4httpData.message;if(typeof(x4httpData.message.error)!='undefined'){var msg="ERRORS REPORTED:";for(idx in x4httpData.message.error){msg+="\n\n"+x4httpData.message.error[idx];x4.debug(x4httpData.message.error[idx]);}
alert(msg);}
if(typeof(x4httpData.message.debug)!='undefined'){x4.debug("Server debug messages follow:");for(idx in x4httpData.message.debug){x4.debug(x4httpData.message.debug[idx]);}
x4.debug("End of server debug messages.");}}
if(typeof(x4httpData.data)!='undefined'){x4.data=x4httpData.data;}
if(x4httpAfterHandler){x4httpAfterHandler();x4httpAfterHandler=false;}
if(x4httpAfterObject){x4httpAfterObject[x4httpAfterMethod]();x4httpAfterObject=false;}},setStatus:function(text){x4.byId('statusLeft').innerHTML=text;},bodyKeyPress:function(e){var Label=x4.keyLabel(e);x4.debug("x4.bodyKeyPress: INVOKED with "+Label,'keyboard');var xx=x4Layers.displayLayers.length-1;if(x4Layers.displayLayers[xx].bodyKeyPress(e))return;x4.debug("x4.bodyKeyPress: Layer did not handle, continuing",'keyboard');keycode=x4.keyCode(e);if(keycode>=112&&keycode<=123){var objnum=keycode-111;var objname='object_for_f'+objnum.toString();obj=x4.byId(objname);if(obj){x4.debug("x4.bodyKeyPress: Invoking "+objname,'keyboard');obj.onclick(e);e.preventDefault();return false;}}
if(keycode==13){obj=x4.byId('object_for_enter');if(obj){x4.debug("x4.bodyKeyPress: Invoking object_for_enter",'keyboard');obj.onclick();return false;}}
return false;},byId:function(id){return document.getElementById(id);},debug:function(msg,msgType){if(msgType==null)msgType='all';if(typeof(x4.debugTypes[msgType])=='undefined'){x4.debugTypes[msgType]=true;}
if(x4.debugTypes.all||x4.debugTypes[msgType]){if(typeof(console)!='undefined'){console.log(msgType+": "+msg);}}},debugTypes:{all:false,keyboard:false},getProperty:function(object,property,defvalue){defvalue=defvalue==null?false:defvalue;if(object==null)return defvalue
if(typeof(object[property])=='undefined')return defvalue;else return object[property];},getSingular:function(pluralName){return pluralName.slice(0,pluralName.length-1);},createElement:function(type,parent,innerHTML){var elm=document.createElement(type);if(innerHTML!=null){elm.innerHTML=innerHTML;}
if(parent!=null){parent.appendChild(elm);}},appendBR:function(parent){var br=document.createElement('BR');parent.appendChild(br);},appendLink:function(parent,text,url){var a=document.createElement('A');a.href=url;a.innerHTML=text;parent.appendChild(a);return a;},addEventListener:function(element,type,expression,uc){if(uc==null){uc=false;}
if(element.addEventListener){element.addEventListener(type,expression,uc);return true;}
else if(element.attachEvent){element.attachEvent('on'+type,expression);return true;}
else return false;},removeEventListener:function(el,eType,fn){if(el.removeEventListener){el.removeEventListener(eType,fn,false);}
else if(el.detachEvent){el.detachEvent('on'+eType,fn);}
else{return false;}},keyCode:function(e){if(window.event)
return window.event.keyCode;else
return e.keyCode;},charCode:function(e){if(window.event)
return window.event.charCode;else
return e.charCode;},keyLabel:function(e){if(window.event)
var x=window.event.keyCode;else
var x=e.keyCode;var x4Keys=new Object();x4Keys['9']='Tab';x4Keys['13']='Enter';x4Keys['16']='';x4Keys['17']='';x4Keys['18']='';x4Keys['20']='CapsLock';x4Keys['27']='Esc';x4Keys['33']='PageUp';x4Keys['34']='PageDown';x4Keys['35']='End';x4Keys['36']='Home';x4Keys['37']='LeftArrow';x4Keys['38']='UpArrow';x4Keys['39']='RightArrow';x4Keys['40']='DownArrow';x4Keys['45']='Insert';x4Keys['46']='Delete';x4Keys['112']='F1';x4Keys['113']='F2';x4Keys['114']='F3';x4Keys['115']='F4';x4Keys['116']='F5';x4Keys['117']='F6';x4Keys['118']='F7';x4Keys['119']='F8';x4Keys['120']='F9';x4Keys['121']='F10';x4Keys['122']='F11';x4Keys['123']='F12';if(typeof(x4Keys[x])=='undefined')return'';var prefix='';if(e.ctrlKey)prefix='Ctrl';if(e.altKey)prefix+='Alt';if(e.shiftKey)prefix+='Shift';var retval=prefix+x4Keys[x];return retval;},popup:function(url,caption){w=770;h=700;mytop=100;myleft=100;settings="width="+w+",height="+h+",top="+mytop+",left="+myleft+", "+"scrollbars=yes,resizable=yes";win=window.open(url,'Popup',settings);win.focus();},db:{getString:'',init:function(table,action){this.getString="x4xAjax="+action
+"&x4xTable="+table;},addValue:function(name,value){this.getString+="&x4c_"+name+"="+encodeURIComponent(value);},addFilter:function(name,value){this.getString+="&x4w_"+name+"="+encodeURIComponent(value);},addParm:function(name,value){this.getString+="&"+name+"="+encodeURIComponent(value);},execute:function(freturn,oreturn,omethodName){x4.ajax(this.getString,freturn,oreturn,omethodName);},getRows:function(table){if(typeof(x4.data[table])=='undefined'){return[];}
else{return x4.data[table];}}}}
var x4HTML={tableRowFromArray:function(theArray,makeTH){var telem=(makeTH==null)?'TD':'TH';var tr=document.createElement('TR');for(var idx in theArray){var cell=document.createElement(telem);cell.innerHTML=theArray[idx];tr.appendChild(cell);}
return tr;}}
var x4String={lTrim:function(value){var re=/\s*((\S+\s*)*)/;return value.replace(re,"$1");},rTrim:function(value){var re=/((\s*\S+)*)\s*/;return value.replace(re,"$1");},trim:function(value){return x4String.lTrim(x4String.rTrim(value));}}
function x4db(table,action){this.getString="x4xAjax="+action
+"&x4xTable="+table;this.addValue=function(name,value){this.getString+="&x4c_"+name+"="+encodeURIComponent(value);}
this.addFilter=function(name,value){this.getString+="&x4w_"+name+"="+encodeURIComponent(value);}
this.execute=function(freturn,oreturn,omethodName){x4.ajax(this.getString,freturn,oreturn,omethodName);}}
var x4Layers={displayLayers:[],ddPages:{},prepareDisplayLayer:function(page,desc){this.nextLayer=new displayLayer(page,desc);},pushDisplayLayer:function(obj){var index=this.displayLayers.length;this.displayLayers[index]=obj;if(typeof(obj.HTML)!='undefined'){x4.byId('andromeda_main_content').innerHTML=obj.HTML;}
else{x4.byId('andromeda_main_content').innerHTML='';x4.byId('andromeda_main_content').appendChild(obj.h);}
document.title=obj.title;obj.onPush();},abortNewLayer:function(){this.refreshDisplayLayer();},popDisplayLayer:function(){this.displayLayers.pop();this.refreshDisplayLayer();},refreshDisplayLayer:function(){var index=this.displayLayers.length-1;var obj=this.displayLayers[index];x4.byId('andromeda_main_content').innerHTML='';x4.byId('andromeda_main_content').appendChild(obj.h);document.title=obj.title;obj.onRestore();},getCurrentDisplayLayer:function(){return this.displayLayers[this.displayLayers.length-1];}}
var x4Input={make:function(colinfo,context){colinfo.name=colinfo.column_id;if(colinfo.type_id=='gender'){var input=document.createElement('SELECT');var opt=document.createElement('OPTION');opt.value='M';opt.innerHTML='M'
input.appendChild(opt);opt=document.createElement('OPTION');opt.value='F';opt.innerHTML='F';input.appendChild(opt);}
else if(colinfo.type_id=='cbool'){var input=document.createElement('SELECT');var opt=document.createElement('OPTION');opt.value='Y';opt.innerHTML='Y'
input.appendChild(opt);opt=document.createElement('OPTION');opt.value='N';opt.innerHTML='N';input.appendChild(opt);}
else if(colinfo.type_id=='time'){var input=document.createElement('SELECT');var x=0;while(x<1440){var opt=document.createElement('OPTION');opt.value=x;opt.innerHTML=this.tStr[x];input.appendChild(opt);x+=15;}}
else{var input=document.createElement('INPUT');input.size=colinfo['dispsize']>24?24:colinfo['dispsize'];input.maxlength=colinfo['dispsize'];if(colinfo.type_id=='dtime'){input.size=25;}}
input.x_dd=colinfo;input.x_save='';input.x_mode='';input.x_error=false;input.x_errorspan=false;input.x_old='';if(input.x_dd.table_id_fko!=''){input.autoComplete=false;}
input.establishValue=function(value,mode){this.setValue(value);this.x_save=this.value;this.setMode(mode);}
input.setValue=function(value){if(this.x_dd.type_id=='dtime'){if(value!=''&&value!=null){this.value=value.slice(0,19);}}
else if(this.x_dd.type_id=='date'){var vx=value;if(vx==null){this.value='';}
else{this.value=vx.slice(5,7)
+'/'+vx.slice(8,10)
+'/'+vx.slice(0,4);}}
else{if(value!=null)
this.value=x4String.trim(value);else
this.value='';}}
input.setError=function(msg){this.x_error=true;if(this.x_errorspan){this.x_errorspan.innerHTML=msg;}
this.setColor();}
input.setMode=function(mode){this.x_mode=mode;this.x_error=false;if(this.x_errorspan){this.x_errorspan.innerHTML='';}
input.readOnly=false;var noro='*NONE*DEFAULT*SEQDEFAULT*BLANK*QUEUEPOS*DOMINANT**';if(noro.indexOf('*'+this.x_dd.automation_id+'*')==-1){input.readOnly=true;}
else{if(mode=='upd'){if(this.x_dd.primary_key=='Y'){this.readOnly=true;}}}
if(mode=='blank')input.readOnly=true;this.setColor();}
input.setColor=function(x_selected){if(x_selected!=null)this.x_selected=x_selected;var suffix=this.x_selected?'Selected':'';if(this.x_error){this.className='x4err'+suffix;return;}
if(this.x_mode=='upd'){if(this.readOnly){if(this.x_dd.primary_key=='Y')
this.className='x4pk';else
this.className='x4ro';}
else{if(this.value==this.x_save)
this.className='x4upd'+suffix;else
this.className='x4ins'+suffix;}}
if(this.x_mode=='ins'){this.className='x4ins'+suffix;}}
if(context==null){input.tabIndex=0;input.x_context=null;return input;}
input.x_context=context;if(x4.getProperty(context,'idPrefix','')!=''){input.id=context.idPrefix+colinfo['name'];}
x4Input.setTabIndex(input,context);if(x4.getProperty(context,'inputsByName','')==''){context.inputsByName=new Object();}
context.inputsByName[colinfo['name']]=input;if(x4.getProperty(context,'firstFocus','')==''){context.firstTab=input;context.firstFocus=input;}
context.lastTab=input;if(x4.getProperty(context,'inputOnFocus','')!=''){input.onfocus=function(e){this.x_context.inputOnFocus(this,e);}}
if(x4.getProperty(context,'inputOnBlur','')!=''){input.onblur=function(e){this.x_context.inputOnBlur(this,e);}}
if(x4.getProperty(context,'inputOnKeyUp','')!=''){input.onkeyup=function(e){x4Input.fieldFormat(this);this.x_context.inputOnKeyUp(this,e);}}
else{input.onkeyup=function(e){x4Input.fieldFormat(this);}}
if(x4.getProperty(context,'inputOnKeyPress','')!=''){input.onkeypress=function(e){this.x_context.inputOnKeyPress(this,e);}}
if(x4.getProperty(context,'inputOnKeyDown','')!=''){input.onkeydown=function(e){this.x_context.inputOnKeyDown(this,e);}}
if(x4.getProperty(context,'inputOnChange','')!=''){input.onchange=function(e){this.x_context.inputOnChange(this,e);}}
return input;},setTabIndex:function(input,context){context.tabIndex++;input.tabIndex=context.tabIndex;if(x4.getProperty(context,'tabLoop','')==''){context.tabLoop=new Array();}
var l=context.tabLoop.length;context.tabLoop[l]=input;if(l==0){input.i_next=null;input.i_prev=null;}
else{input.i_prev=context.tabLoop[l-1];context.tabLoop[l-1].i_next=input;context.tabLoop[0].i_prev=input;input.i_next=context.tabLoop[0];}},fieldFormat:function(input){objval=x4String.trim(input.value);if(input.x_dd.type_id=='date'){if(objval.length==6){if(objval.indexOf('/')==-1){if(objval.indexOf('-')==-1){var month=objval.substr(0,2);var day=objval.substr(2,2);var year=objval.substr(4,2);yearint=parseInt(year);if(yearint<30){year='20'+year}
else{year='19'+year}
input.value=month+'/'+day+'/'+year;}}}}},tStr:{0:'12:00 am',15:'12:15 am',30:'12:30 am',45:'12:45 am',60:' 1:00 am',75:' 1:15 am',90:' 1:30 am',105:' 1:45 am',120:' 2:00 am',135:' 2:15 am',150:' 2:30 am',165:' 2:45 am',180:' 3:00 am',195:' 3:15 am',210:' 3:30 am',225:' 3:45 am',240:' 4:00 am',255:' 4:15 am',270:' 4:30 am',285:' 4:45 am',300:' 5:00 am',315:' 5:15 am',330:' 5:30 am',345:' 5:45 am',360:' 6:00 am',375:' 6:15 am',390:' 6:30 am',405:' 6:45 am',420:' 7:00 am',435:' 7:15 am',450:' 7:30 am',465:' 7:45 am',480:' 8:00 am',495:' 8:15 am',510:' 8:30 am',525:' 8:45 am',540:' 9:00 am',555:' 9:15 am',570:' 9:30 am',585:' 9:45 am',600:'10:00 am',615:'10:15 am',630:'10:30 am',645:'10:45 am',660:'11:00 am',675:'11:15 am',690:'11:30 am',705:'11:45 am',720:'12:00 pm',735:'12:15 pm',750:'12:30 pm',765:'12:45 pm',780:' 1:00 pm',795:' 1:15 pm',810:' 1:30 pm',825:' 1:45 pm',840:' 2:00 pm',855:' 2:15 pm',870:' 2:30 pm',885:' 2:45 pm',900:' 3:00 pm',915:' 3:15 pm',930:' 3:30 pm',945:' 3:45 pm',960:' 4:00 pm',975:' 4:15 pm',990:' 4:30 pm',1005:' 4:45 pm',1020:' 5:00 pm',1035:' 5:15 pm',1050:' 5:30 pm',1065:' 5:45 pm',1080:' 6:00 pm',1095:' 6:15 pm',1110:' 6:30 pm',1125:' 6:45 pm',1140:' 7:00 pm',1155:' 7:15 pm',1170:' 7:30 pm',1185:' 7:45 pm',1200:' 8:00 pm',1215:' 8:15 pm',1230:' 8:30 pm',1245:' 8:45 pm',1260:' 9:00 pm',1275:' 9:15 pm',1290:' 9:30 pm',1305:' 9:45 pm',1320:'10:00 pm',1335:'10:15 pm',1350:'10:30 pm',1365:'10:45 pm',1380:'11:00 pm',1395:'11:15 pm',1410:'11:30 pm',1425:'11:45 pm'}}
var x4Select={divWidth:400,divHeight:300,div:false,iframe:false,row:false,onKeyUp:function(obj,strParms,e){var kc=e.keyCode;if(kc==9||kc==13){return true;}
if(kc==38||kc==40){if(!x4Select.visible())return;if(x4Select.div.firstChild.rows.length==0)return;if(!x4Select.row){var row=x4Select.div.firstChild.rows[0];var skey=row.x_skey;x4Select.mo(row,skey);return;}
var row=x4.byId('as'+x4Select.row);var prev=row.getAttribute('x_prev');var next=row.getAttribute('x_next');if(kc==38){if(prev){var row=x4.byId('as'+prev);x4Select.mo(row,prev);}}
if(kc==40){if(next){var row=x4.byId('as'+next);x4Select.mo(row,next);}}
return;}
if(typeof(obj.androSelect=='undefined')){obj.androSelect='';}
if(obj.androSelect==obj.value){return;}
if(!this.div){this.div=document.createElement('DIV');this.div.style.display='none';this.div.style.width=this.divWidth+"px";this.div.style.height=this.divHeight+"px";this.div.style.position='absolute';this.div.className='androSelect';this.div.id='androSelect';document.body.appendChild(x4Select.div);var x=document.createElement('TABLE');x4Select.div.appendChild(x);}
if(x4Select.div.style.display=='none'){var postop=obj.offsetTop;var poslft=obj.offsetLeft;var objpar=obj;while((objpar=objpar.offsetParent)!=null){postop+=objpar.offsetTop;poslft+=objpar.offsetLeft;}
x4Select.div.style.top=(postop+obj.offsetHeight)+"px";x4Select.div.style.left=poslft+"px";x4Select.div.style.display='block';x4.addEventListener(document,'click',x4Select_documentClick);}
this.control=obj;this.row=false;var url='x4xDropdown='+obj.x_dd.table_id_fko
+'&gpv=2&gp_letters='+obj.value.replace(" ","+");x4.ajax(url,null,this,'handler');},handler:function(){if(typeof(x4httpData.x4Select)!='undefined'){this.div.firstChild.innerHTML=x4httpData.x4Select.rows;}
if(this.div.firstChild){var table=this.div.firstChild;if(table.rows.length>0){table.rows[0].onmouseover();}}},onKeyDown:function(e){var kc=e.keyCode;if(kc==9||kc==13){if(!x4Select.visible())return true;if(x4Select.div.firstChild.rows.length==0){x4Select.hide();return true;}
if(this.row){var row=x4.byId('as'+x4Select.row);var pk=row.getAttribute('x_value');this.control.value=pk;}
this.hide();return true;}},hide:function(){this.div.firstChild.innerHTML=''
this.div.style.display='none';},visible:function(){if(this.div==false)return false;if(this.div.style.display=='none')return false;return true;},mo:function(tr,skey){if(x4.byId('as'+this.row)){x4.byId('as'+this.row).className='';}
this.row=skey;tr.className='hilite';},click:function(value,suppress_focus){this.control.value=value;this.hide();if(suppress_focus==null){this.control.focus();}}}
function x4Select_documentClick(){x4Select.hide();x4.removeEventListener(document,'click',x4Select_documentClick);return false;}
var x4Pages={load:function(x4xPage,desc,objParent){if(desc==null){desc=x4xPage;}
x4Layers.prepareDisplayLayer(x4xPage,desc);if(objParent!=null){x4Layers.nextLayer.objParent=objParent;}
x4.debug("Page request: fetching dd from server");var div=document.createElement('div');div.style.textAlign='center';div.style.paddingTop='200px';var img=document.createElement('img');img.src='clib/ajax-loader.gif';div.appendChild(img);var span=document.createElement('span');span.innerHTML='&nbsp;&nbsp;Loading Page: '+desc+'</div>';div.appendChild(span);x4.byId('andromeda_main_content').innerHTML='';x4.byId('andromeda_main_content').appendChild(div);getString='x4xPage='+x4xPage;x4.ajax(getString,null,this,'loadReturnHandler');},loadReturnHandler:function(){if(x4.getProperty(x4httpData,'page','')==''){console.log('aborting a layer');x4Layers.abortNewLayer();return;}
var x4xPage=x4Layers.nextLayer.page;x4Layers.ddPages[x4xPage]=x4httpData.page.data;this.displayPage(x4xPage);},displayPage:function(x4xPage){var pageInfo=x4Layers.ddPages[x4xPage];if(typeof(pageInfo.HTML)!='undefined'){var x_context=x4Layers.nextLayer;x4Layers.nextLayer=false;x_context.HTML=x4Layers.ddPages[x4xPage].HTML;x4Layers.pushDisplayLayer(x_context);if(typeof(pageInfo.Script)!='undefined'){eval(pageInfo.Script);}
x_context.onRestore();}
else{displayLayerBrowse(x4xPage);}}}
var x4Menu={x4letters:['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'],x4numbers:['1','2','3','4','5','6','7','8','9'],menuCol:false,menuRow:false,menuCols:new Array(),title:'Main Menu',onPush:function(){return false;},onRestore:function(){return false;},init:function(){this.h=document.createElement('DIV');this.h.className='mainmenu';var xx=document.createElement('H1');xx.innerHTML='MAIN MENU';this.h.appendChild(xx);var topTab=document.createElement('TABLE');var row=topTab.insertRow(topTab.rows.length);topTab.className='gsMenu';this.h.appendChild(topTab);this.initCols(row);this.hiliteCol(this.menuCols[0]);this.hiliteRow(this.menuCols[0].x_kids[0]);x4Layers.pushDisplayLayer(this);},initCols:function(row){var idx=-1;var retval=null;var x4menu=x4httpData.menu.default;for(var module in x4menu){idx++;var td=document.createElement('TD');td.x_index=idx;td.x_kids=new Array();td.x_context=this;td.onmouseover=function(){this.x_context.hiliteCol(this);}
td.className='scheme0 dim';row.appendChild(td);if(idx==0)retval=td;this.menuCols[idx]=td;var desc=x4menu[module].description;var a=document.createElement('H4');a.href='javascript:void(0);';a.innerHTML=this.x4numbers[idx]+'. '+desc;td.appendChild(a);this.initRows(x4menu,td,module);}
return retval;},initRows:function(x4menu,td,module){var idx=-1;for(var page in x4menu[module].items){idx++;var desc=x4menu[module].items[page].description;var a=document.createElement('A');a.href='javascript:void(0);';a.innerHTML=this.x4letters[idx]+'. '+desc;a.x_context=this;a.onmouseover=function(){this.x_context.hiliteRow(this);}
a.x_page=page;a.x_desc=desc;a.x_index=idx;a.onclick=function(){this.x_context.chooseItem(this);}
td.appendChild(a);td.x_kids[td.x_kids.length]=a;}},hiliteCol:function(td){if(this.menuCol!=td){if(this.menuRow){this.menuRow.className='';this.menuRow=false;}
if(this.menuCol){this.menuCol.className='scheme0 dim';this.menuCol=false;}}
td.className='scheme0';this.menuCol=td;},hiliteRow:function(a){if(this.menuRow){this.menuRow.className='';this.menuRow=false;}
a.className='hilite';this.menuRow=a;},chooseItem:function(a){x4Pages.load(a.x_page,a.x_desc);},bodyKeyPress:function(e){var charCode=x4.charCode(e);var keyCode=x4.keyCode(e);if(typeof(this.menuCols[charCode-49])!='undefined'){this.hiliteCol(this.menuCols[charCode-49]);this.hiliteRow(this.menuCols[charCode-49].x_kids[0]);return true;a}
if(this.menuCol&&charCode>=97){var index=charCode-97;if(index<=(this.menuCol.x_kids.length-1)){this.chooseItem(this.menuCol.x_kids[index]);return true;}}
if(keyCode==13&&this.menuRow){this.chooseItem(this.menuRow);return true;}
if(keyCode==40||keyCode==38){if(this.menuRow){if(!this.menuRow){this.hiliteRow(this.menuCol.x_kids[0]);}
else{if(keyCode==38){if(this.menuRow.x_index>0){this.hiliteRow(this.menuCol.x_kids[this.menuRow.x_index-1]);}}
if(keyCode==40){if(this.menuRow.x_index<(this.menuCol.x_kids.length-1)){this.hiliteRow(this.menuCol.x_kids[this.menuRow.x_index+1]);}}}}
return true;}
if(keyCode==37||keyCode==39){if(this.menuCol){var rowIndex=0;if(this.menuRow){rowIndex=this.menuRow.x_index;}
else{rowIndex=0;}
var newGuy=false;if(keyCode==37&&this.menuCol.x_index>0){newGuy=this.menuCols[this.menuCol.x_index-1];}
if(keyCode==39&&this.menuCol.x_index<(this.menuCols.length-1)){newGuy=this.menuCols[this.menuCol.x_index+1];}
if(newGuy){this.hiliteCol(newGuy);if(rowIndex>(newGuy.x_kids.length-1))
rowIndex=newGuy.x_kids.length-1;this.hiliteRow(newGuy.x_kids[rowIndex]);}}
return true;}
return false;}}
var x4Print={dialog:function(controls,page,title){var getString="x4Index.php?x4xReport="+page;for(var idx in controls){var cid=controls[idx];getString+="&x4c_"+cid+"="
+encodeURIComponent(x4.byId(cid).value);}
var div=x4Dialogs.blank(title);x4.createElement('h3',div,'Print: '+title);x4.appendBR(div);var a=x4.appendLink(div,'Preview as PDF',getString);a.onclose=function(){x4Dialogs.close();}
x4Dialogs.show();}}
var x4Dialogs={div:false,blank:function(){if(!this.div){this.div=document.createElement('DIV');this.div.style.display='none';this.div.style.width="500px";this.div.style.height="350px";this.div.style.top="100px";this.div.style.left="100px";this.div.style.padding="10px";this.div.style.position='absolute';this.div.className='androSelect';this.div.id='androDialog';document.body.appendChild(this.div);}
this.div.innerHTML="";var a=document.createElement('A');a.style.float='right';a.href="javascript:x4Dialogs.close()";a.innerHTML="Close";this.div.appendChild(a);x4.createElement('br',this.div,'');return this.div;},show:function(){if(this.div)this.div.style.display='block';},hide:function(){if(this.div)this.div.style.display='none';},close:function(){if(this.div){this.hide();}}}
function displayLayer(page,desc){this.page=page;this.title=desc;this.objParent=false;this.initHasRun=false;this.tabIndex=1000;this.lastFocus=false;this.firstFocus=false;this.lastTab=false;this.firstTab=false;this.surface=false;this.onPush=function(){return false;}
this.onRestore=function(){return false;}
this.setFocus=function(id){if(typeof(id)=='string')
var obj=x4.byId(id);else
var obj=id;if(typeof(obj.focus)!='undefined'){obj.focus();}
if(typeof(obj.onfocus)!='undefined'){obj.onfocus();}}
this.fixFocus=function(){if(x4.getProperty(this,'lastFocus')){this.setFocus(this.lastFocus);}
else if(x4.getProperty(this,'firstFocus')){this.setFocus(this.firstFocus);}}
this.bodyKeyPress=function(e){Label=x4.keyLabel(e);x4.debug("displayLayer(generic).bodyKeyPress: INVOKED with "+Label,'keyboard');if(Label=='Esc'){x4Layers.popDisplayLayer();return true;}
return false;}
this.inputsNavigationTab=function(input,e){var Label=x4.keyLabel(e);if(Label=='Tab'){this.inputsNavigateNext(input);e.preventDefault();return false;}
if(Label=='ShiftTab'){this.inputsNavigatePrev(input);e.preventDefault();return false;}
return true;}
this.inputsNavigationEnter=function(input,e){var Label=x4.keyLabel(e);if(Label=='Enter'){this.inputsNavigateNext(input);e.preventDefault();return false;}
if(Label=='ShiftEnter'){this.inputsNavigatePrev(input);e.preventDefault();return false;}
return true;}
this.inputsNavigationArrows=function(input,e){var Label=x4.keyLabel(e);if(input.tagName=='INPUT'){if(Label=='DownArrow'){this.inputsNavigateNext(input);e.preventDefault();return false;}
else if(Label=='UpArrow'){this.inputsNavigatePrev(input);e.preventDefault();return false;}}}
this.inputsNavigateNext=function(input){var inputOrig=input;while(input.i_next.readOnly==true){input=input.i_next;if(input==inputOrig){return;}}
this.setFocus(input.i_next);}
this.inputsNavigatePrev=function(input){var inputOrig=input;while(input.i_prev.readOnly==true){input=input.i_prev;if(input==inputOrig){return;}}
this.setFocus(input.i_prev);}
this.setupStandard=function(){this.onRestore=function(){this.fixFocus();}
this.onPush=function(){this.fixFocus();}
this.inputOnKeyPress=function(input,e){this.inputsNavigationTab(input,e);this.inputsNavigationEnter(input,e);}}
this.appendChild=function(obj){if(!this.surface){this.surface=x4.byId('andromeda_main_content');}
this.surface.appendChild(obj);}
this.setSurfaceById=function(id){this.surface=x4.byId(id);}
this.addTitle=function(text){var span=document.createElement('H3');span.innerHTML=text;this.appendChild(span);}
this.addCaption=function(text){var span=document.createElement('SPAN');span.innerHTML=text+' ';this.appendChild(span);}
this.newLine=function(){var br=document.createElement('BR');this.appendChild(br);}
this.addDateInput=function(id){var colinfo={table_id:'',column_id:'',type_id:'date',dispsize:11}
var input=x4Input.make(colinfo,this);input.id=id;this.appendChild(input);}
this.addLookup=function(id,table){var colinfo={table_id:'',column_id:'',type_id:'date',dispsize:11};colinfo.table_id_fko=table;var input=x4Input.make(colinfo,this);input.id=id;input.onkeydown=function(e){x4Select.onKeyDown(e);}
input.onkeyup=function(e){x4Select.onKeyUp(this,'gp_dropdown=customers',e);}
this.appendChild(input);}}
function displayLayerBrowse(x4xPage){var x_browse=x4Layers.nextLayer;x4Layers.nextLayer=false;x_browse.build=function(){this.dd=x4Layers.ddPages[x4xPage];this.tabIndex=1000;this.idPrefix='x4w_';this.table_id=x4Layers.ddPages[x4xPage].table_id;this.sortCol='';this.sortDir='ASC';this.collist=x4Layers.ddPages[x4xPage].aProjections._uisearch;this.dd.data=new Object()
this.dd.data.rowCount=0;this.dd.data.rowSelected=-1;this.dd.data.rows=new Array();this.dd.data.rowNext=-1;this.returnall=false;this.displayInputs=true;if(this.objParent||x4.getProperty(this.dd,'returnall','')=='Y'){this.returnall=true;this.displayInputs=false;}
this.pkValues=new Array();if(this.objParent){this.pkValues=this.objParent.getPKValues();}
var center=document.createElement('div');this.h=center;var div=document.createElement('DIV');div.className='browse';this.h.appendChild(div);var title=document.createElement('H1');title.innerHTML=this.title;div.appendChild(title);var linknew=document.createElement('A');linknew.id='object_for_f7';linknew.x_context=this;linknew.href="javascript:void(0)";linknew.innerHTML='F7: New '+x4.getSingular(this.dd.description);linknew.className='browse_anew';linknew.onclick=function(e){displayLayerDetail(this.x_context.dd.table_id,'ins',null,this.x_context);}
div.appendChild(linknew);x4.createElement('BR',div,'');x4.createElement('BR',div,'');var hTab=document.createElement('TABLE');hTab.className='browseHead';hTab.x_context=this;div.appendChild(hTab);var hDiv=document.createElement('DIV');hDiv.style.display='none';hDiv.setAttribute('align','left');hDiv.className='browseBody';div.appendChild(hDiv);var hTabb=document.createElement('TABLE');hTabb.setAttribute('align','left');hDiv.appendChild(hTabb);hTabb.x_context=this;hTabb.style.height="370px";this.divBody=hDiv;this.htmlTable=hTabb;this.hBrowseBody=hTabb;var charWidth=x4.byId('x4CharacterInformation').scrollWidth;var tabWidth=0;var trh=hTab.insertRow(0);if(this.displayInputs){var tri=hTab.insertRow(1);}
this['headers']=new Object();for(var idx in this.collist){column=this.dd.flat[this.collist[idx]];var dw=column.dispsize;if(column.dispsize<column['description'].length){dw=column['description'].length;}
console.debug(column.dispsize+" : "+dw+" : "+charWidth);var pw=Number(charWidth)*Number(dw);this.dd.flat[this.collist[idx]].sbWidth=pw;tabWidth+=pw;if(this.sortCol==''){this.sortCol=column.column_id
this.sortDir='DESC';}
var cellh=document.createElement('TH');cellh.style.width=pw+"px";var cellhdiv=document.createElement('DIV');cellhdiv.style.maxWidth=pw+"px";cellhdiv.style.overflow="hidden";cellhdiv.innerHTML=column['description'];cellh.appendChild(cellhdiv);cellh.style.width=pw+"px";cellh.x_caption=column['description'];cellh.x_context=this;cellh.x_name=column.column_id
cellh.onmouseover=function(e){this.className='hilite';}
cellh.onmouseout=function(e){if(this.x_name==this.x_context.sortCol){this.className='sorted';}
else{this.className='';}}
cellh.onclick=function(e){this.x_context.setSort(this.x_name,null);this.x_context.fixFocus();}
trh.appendChild(cellh);this['headers'][column.column_id]=cellh;if(this.displayInputs){var cell=document.createElement('TD');cell.style.width=pw+"px";var input=x4Input.make(column,this);input.style.width=(pw-3)+"px";cell.appendChild(input);tri.appendChild(cell);}}
tabWidth+=2*this.collist.length;hDiv.style.width=tabWidth+"px";hDiv.style.height="370px";hDiv.style.overflow='auto';x_browse.setSort(this.sortCol,'DESC',true);x4Layers.pushDisplayLayer(this);}
x_browse.onRestore=function(){this.fixFocus();}
x_browse.onPush=function(){this.fixFocus();if(this.returnall){this.fetchData(true);}
this.initHasRun=true;}
x_browse.bodyKeyPress=function(e){Label=x4.keyLabel(e);x4.debug("x_browse.bodyKeyPress: INVOKED with "+Label,'keyboard');if(Label=='Esc'){if(this.returnall){x4Layers.popDisplayLayer();}
else{if(this.dd.data.rowCount==0){x4Layers.popDisplayLayer();}
else{for(var idx in this.tabLoop){input=this.tabLoop[idx];input.x_old='';input.value='';}
this.purgeDataAndHTML();}}
return true;}
if(Label!=''){if(this.keysArrowsAndPages(Label,null,e))
return true;}
if(Label=='Enter'&&this.dd.data.rowSelected>=0){this.editRow(this.dd.data.rowSelected);return true;}
return false;}
x_browse.inputOnKeyPress=function(input,e){var Label=x4.keyLabel(e);x4.debug("x_browse.inputOnKeyPress INVOKED with "+Label,'keyboard');if(Label!=''){if(this.keysArrowsAndPages(Label,input,e))
return true;}
this.fetchData();this.inputsNavigationTab(input,e);}
x_browse.inputOnKeyUp=function(input,e){var Label=x4.keyLabel(e);if(Label==''){x4.debug('x_browse.inputOnKeyUp INVOKED for unlabeled key','keyboard');this.fetchData();}}
x_browse.keysArrowsAndPages=function(Label,input,e){x4.debug("x_browse.keysArrowsAndPages INVOKED with "+Label,'keyboard');if(Label=='DownArrow'){console.log("caught a down arrow");if(this.dd.data.rowSelected<(this.dd.data.rowCount-1)){console.log("choosing Row + 1");this.chooseRow(this.dd.data.rowSelected+1);}
e.preventDefault();return true;}
if(Label=='UpArrow'){if(this.dd.data.rowSelected>0){this.chooseRow(this.dd.data.rowSelected-1);}
e.preventDefault();return true;}
if(Label=='CtrlPageUp'||Label=='Home'){this.chooseRow(0);e.preventDefault();return true;}
if(Label=='PageUp'){var x=0;if(this.dd.data.rowSelected>19)x=this.dd.data.rowSelected-20;this.chooseRow(x);e.preventDefault();return true;}
if(Label=='CtrlPageDown'||Label=='End'){this.chooseRow(this.dd.data.rowCount-1);e.preventDefault();return true;}
if(Label=='PageDown'){var x=Number(this.dd.data.rowCount)-1;if(this.dd.data.rowSelected<(this.dd.data.rowCount-20))
x=this.dd.data.rowSelected+20;this.chooseRow(x);e.preventDefault();return true;}
if(Label=='ShiftUpArrow'){if(input!=null){this.setSort(input.x_dd.column_id,'DESC');}
e.preventDefault();return true;}
if(Label=='ShiftDownArrow'){if(input!=null){this.setSort(input.x_dd.column_id,'ASC');}
e.preventDefault();return true;}}
x_browse.inputOnFocus=function(input,e){this.lastFocus=input;}
x_browse.setSort=function(sortCol,sortDir,nofetch){if(sortDir==null){if(sortCol==this.sortCol){sortDir=this.sortDir=='ASC'?'DESC':'ASC';}
else{sortDir='ASC';}}
if(sortCol!=this.sortCol){var header=this.headers[this.sortCol];}
this.sortCol=sortCol;this.sortDir=sortDir;var header=this.headers[this.sortCol];if(sortDir=='ASC'){}
else{}
if(nofetch==null){this.fetchData(true);}}
x_browse.purgeDataAndHTML=function(){this.dd.data.rowSelected=-1;this.dd.data.rowCount=0;this.dd.data.rows=new Object();this.hBrowseBody.innerHTML='';x4.setStatus('0 rows');}
x_browse.fetchData=function(force,offset){x_browse.rowNext=-1;var goforit=false;var getVals='';if(this.returnall){for(var idx in this.pkValues){getVals
+='&x4w_'+idx+'='
+encodeURIComponent(this.pkValues[idx]);}}
else{for(var idx in this.tabLoop){var input=this.tabLoop[idx];var valOld=input.x_old;var valNew=input.value;if(valOld!=valNew){if(valNew!=''){goforit=true;}
input.x_old=input.value;}
getVals+='&'+input.id+'='+encodeURIComponent(valNew);}}
if(goforit||force){var getString='x4xAjax=bsrch'
+'&x4xTable='+this.table_id
+'&sortCol='+this.sortCol
+'&sortDir='+this.sortDir
+getVals;x4.ajax(getString,null,this,'returnHandler');}}
x_browse.returnHandler=function(){this.purgeDataAndHTML();var tab=this.table_id;if(typeof(x4httpData.data)!='undefined'){if(typeof(x4httpData.data[tab])!='undefined'){this.dd.data.rows=x4httpData.data[tab];this.dd.data.rowCount=this.dd.data.rows.length;this.dd.data.rowNext=0;browseDisplayRows();}}}
x_browse.replaceRow=function(row){this.dd.data.rows[this.dd.data.rowSelected]=row;this.htmlTable.deleteRow(Number(this.dd.data.rowSelected)+2);this.addRowToDisplay(row,Number(this.dd.data.rowSelected));this.chooseRow(this.dd.data.rowSelected);}
x_browse.deleteSelectedRow=function(){var idx=this.dd.data.rowSelected;this.htmlTable.deleteRow(Number(this.dd.data.rowSelected)+2);if(this.dd.data.rowSelected>0)this.dd.data.rowSelected--;this.chooseRow(this.dd.data.rowSelected);}
x_browse.addRowToSearchResults=function(row){this.dd.data.rowCount++;this.dd.data.rows[this.dd.data.rowCount-1]=row;this.addRowToDisplay(row,Number(this.dd.data.rowCount)-1);this.chooseRow(Number(this.dd.data.rowCount)-1);}
x_browse.addRowToDisplay=function(row,idx,alsoChoose){tbody=this.hBrowseBody;tr=tbody.insertRow(Number(idx));tr.x_idx=idx;tr.id="x4browse_"+tr.x_idx;tr.x_context=this;tr.onmouseover=function(e){this.x_context.chooseRow(this.x_idx);}
tr.onclick=function(e){this.x_context.chooseRow(this.x_idx);this.x_context.fixFocus();this.x_context.editRow(this.x_idx);}
for(var idx in this.collist){td=document.createElement('TD');div=document.createElement('DIV');div.style.maxWidth=this.dd.flat[this.collist[idx]].sbWidth+"px";div.style.width=this.dd.flat[this.collist[idx]].sbWidth+"px";td.style.width=this.dd.flat[this.collist[idx]].sbWidth+"px";div.innerHTML=row[this.collist[idx]];td.appendChild(div);tr.appendChild(td);}
if(alsoChoose!=null){this.chooseRow(this.rowCount-1);}}
x_browse.chooseRow=function(idx){if(this.dd.data.rowSelected!=-1){if(x4.byId("x4browse_"+this.dd.data.rowSelected))
x4.byId("x4browse_"+this.dd.data.rowSelected).className='';}
this.dd.data.rowSelected=idx;if(x4.byId("x4browse_"+idx)){x4.byId("x4browse_"+idx).className='hilite';this.setStatus();}}
x_browse.setStatus=function(){tidx=Number(this.dd.data.rowSelected)+1;x4.setStatus('Row '+tidx+' of '+this.dd.data.rowCount);}
x_browse.editRow=function(idx){displayLayerDetail(this.table_id,'upd',idx,this);}
x_browse.build();}
function browseDisplayRows(){var topLayer=x4Layers.displayLayers.length-1;self=x4Layers.displayLayers[topLayer];self.divBody.style.display='';if(self.dd.data.rowNext==-1)return;var max1=self.dd.data.rowNext+20;var max2=self.dd.data.rowCount-1;var next=max1>max2?max2:max1;for(var x=self.dd.data.rowNext;x<=next;x++){self.addRowToDisplay(self.dd.data.rows[x],x);}
if(self.dd.data.rowNextS==0)self.chooseRow(0);self.dd.data.rowNext=next+1;if(self.dd.data.rowNext<self.dd.data.rowCount-1){setTimeout("browseDisplayRows()",10);}}
function displayLayerDetail(x4xPage,mode,rowIdx,parent){var x_detail=new displayLayer();x_detail.parent=parent;x_detail.build=function(){this.dd=x4Layers.ddPages[x4xPage];this.tabIndex=1000;this.idPrefix='x4c_';this.table_id=x4Layers.ddPages[x4xPage].table_id;this.mode=mode;this.afterExit=false;if(mode=='ins'){this.title=this.dd.description+" (new entry) ";}
else{this.title=this.dd.description+" (viewing) ";}
this.h=document.createElement('DIV');this.h.className='detail';var divm=document.createElement('CENTER');divm.className='detailInner';this.h.appendChild(divm);var dTable=document.createElement('TABLE');dTable.className='detail';var tRow=dTable.insertRow(0);var tCell=tRow.insertCell(0);tCell.colSpan=2;x4.createElement('H1',tCell,this.title);var dRow=dTable.insertRow(1);var d1=dRow.insertCell(0);divm.appendChild(dTable);var div2=document.createElement('DIV');div2.className='x4Box';d1.appendChild(div2);var tab=document.createElement('TABLE');tab.className='x4Detail';div2.appendChild(tab);var idx=0;for(colname in this.dd.flat){column=this.dd.flat[colname];if(column['uino']=='Y')continue;if(colname=='skey')continue;if(colname=='skey_quiet')continue;if(colname=='_agg')continue;var row=tab.insertRow(tab.rows.length);var label=row.insertCell(0);label.className='caption';label.innerHTML=column['description'];if(idx==0){idx=1;label.innerHTML='F9:'+label.innerHTML;label.id='object_for_f9';label.x_context=this;label.onclick=function(){this.x_context.lastFocus.focus();}}
var input=x4Input.make(column,this);input.className='input';var tdinp=row.insertCell(1);tdinp.appendChild(input);}
var tabIndex=3000;var d2=dRow.insertCell(1);d2.className='linksOnRight';var div2=document.createElement('DIV');var goforit=false;for(idx in this.dd.fk_children){goforit=true;var table_chd=this.dd.fk_children[idx];var akid=document.createElement('A');akid.innerHTML=table_chd.description;akid.x_context=this;akid.x_page=idx;akid.x_description=table_chd.description;akid.tabIndex=++tabIndex;akid.href='javascript:void(0);';akid.onclick=function(){x4Pages.load(this.x_page,this.x_description,this.x_context);}
div2.appendChild(akid);x4.createElement('BR',div2,'');}
if(goforit){x4.createElement('BR',div2,'');x4.createElement('BR',div2,'');x4.createElement('DIV',div2,'Actions:');x4.createElement('BR',div2,'');}
var a=document.createElement('A');a.innerHTML='ESC: Back';a.href="javascript:void(0)";a.tabIndex=++tabIndex;a.x_context=this;a.onclick=function(){this.x_context.attemptExit('pop');}
div2.appendChild(a);x4.createElement('BR',div2,'');if(this.mode!='ins'){var a=document.createElement('A');a.innerHTML='Delete';a.href="javascript:void(0)";a.tabIndex=++tabIndex;a.x_context=this;a.onclick=function(){this.x_context.attemptDelete();}
div2.appendChild(a);}
if(goforit){var akids=document.createElement('A');akids.href="javascript:void(0)";akids.id='object_for_f8';akids.innerHTML='F8:LINKS';akids.tabIndex=3000;akids.onclick=function(){this.focus();}
d2.appendChild(akids);x4.createElement('BR',d2,'');x4.createElement('BR',d2,'');d2.appendChild(div2);}
if(rowIdx!=null){this.populateRow(rowIdx);}
x4Layers.pushDisplayLayer(this);}
x_detail.bodyKeyPress=function(e){var Label=x4.keyLabel(e);x4.debug("x_detail.bodyKeyPress: INVOKED label "+Label,'keyboard');if(Label=='Esc'){x4.debug('x_detail.bodyKeyPress: ESC, attemptExit()','keyboard');this.attemptExit('pop');}
if(this.inputsArrowsAndPages(e))return true;x4.debug('x_detail.bodyKeyPress: returning false','keyboard');return false;}
x_detail.inputOnKeyPress=function(input,e){x4.debug('x_detail.inputOnKeyPress: INVOKED','keyboard');this.inputsNavigationTab(input,e);this.inputsNavigationEnter(input,e);this.inputsNavigationArrows(input,e);this.inputsArrowsAndPages(e);}
x_detail.inputsArrowsAndPages=function(e){x4.debug('x_detail.inputsArrowsAndPages: INVOKED','keyboard');Label=x4.keyLabel(e);if(Label=='PageDown'){this.attemptExit('next');return true;}
if(Label=='PageUp'){this.attemptExit('prev');return true;}
if(Label=='CtrlPageUp'){this.attemptExit('first');e.preventDefault();e.stopPropagation();return true;}
if(Label=='CtrlPageDown'){this.attemptExit('last');e.preventDefault();e.stopPropagation();return true;}
return false;}
x_detail.inputOnFocus=function(input,e){input.setColor(true);this.lastFocus=input;}
x_detail.inputOnBlur=function(input,e){input.setColor(false);}
x_detail.inputOnKeyUp=function(input,e){input.setColor();}
x_detail.onPush=function(){this.fixFocus();}
x_detail.onRestore=function(){this.fixFocus();}
x_detail.attemptExit=function(afterExit){this.afterExit=afterExit;var dirty=false;for(idx in this.tabLoop){var inpx=this.tabLoop[idx];if(inpx.value!=inpx.x_save){dirty=true;break;}}
if(!dirty){this.exit();}
else{if(confirm("Save Changes?")){this.attemptSave();}
else{this.exit();}}}
x_detail.exit=function(forceItem){if(forceItem!=null)
var afterExit=forceItem;else
var afterExit=this.afterExit;if(afterExit=='pop')
x4Layers.popDisplayLayer();else
this.navigate();}
x_detail.navigate=function(){var dx=this.dd.data;var rs=Number(dx.rowSelected);var rc=Number(dx.rowCount);if(this.afterExit=='first'&&rs!=0)rn=0;if(this.afterExit=='prev'&&rs!=0)rn=rs-1;if(this.afterExit=='next'&&rs!=(rc-1))rn=rs+1;if(this.afterExit=='last'&&rs!=(rc-1))rn=rc-1;if(rn!=rs){this.populateRow(rn);dx.rowSelected=rn;x4.setStatus('Row '+(rn+1)+' of '+rc);}
this.afterExit='';}
x_detail.attemptSave=function(){var str='x4xTable='+this.dd.table_id
+'&x4xAjax='+this.mode
+'&x4xRetRow=1';if(this.mode=='upd'){var skey=this.dd.data.rows[this.dd.data.rowSelected].skey;str+='&x4w_skey='+skey;}
for(idx in this.tabLoop){var input=this.tabLoop[idx];if(input.readOnly)continue;if(input.value==input.x_save)continue;str+='&x4c_'+input.x_dd.column_id
+'='+encodeURIComponent(input.value);}
x4.ajax(str,null,this,'attemptSaveReturn');}
x_detail.attemptSaveReturn=function(){if(typeof(x4httpData.message)=='undefined'){var row=x4httpData.data[this.table_id][0];if(this.mode=='ins'){this.parent.addRowToSearchResults(row);}
else{this.parent.replaceRow(row,this.dd.data.rowSelected);}
this.exit();}}
x_detail.attemptDelete=function(){var skey=this.dd.data.rows[this.dd.data.rowSelected].skey;var str='x4xTable='+this.dd.table_id
+'&x4xAjax=del'
+'&x4xRetRow=1'
+'&x4w_skey='+skey;x4.ajax(str,null,this,'attemptDeleteReturn');}
x_detail.attemptDeleteReturn=function(){if(typeof(x4httpData.message)=='undefined'){this.parent.deleteSelectedRow();this.exit('pop');}}
x_detail.populateRow=function(rowIdx){var row=this.dd.data.rows[rowIdx];for(var idx in this.tabLoop){input=this.tabLoop[idx];colname=input.x_dd.column_id;if(typeof(row[colname])!='undefined'){input.establishValue(row[colname],'upd');}
else{input.establishValue('','upd');}}}
x_detail.getPKValues=function(){var retval=new Object();var row=this.dd.data.rows[this.dd.data.rowSelected];for(var idx in this.dd.flat){if(this.dd.flat[idx].primary_key=='Y'){retval[idx]=row[idx];}}
return retval;}
x_detail.build();}