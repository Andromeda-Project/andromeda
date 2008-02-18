
var androIE=false;if(navigator.userAgent.indexOf('MSIE')>=0&&navigator.userAgent.indexOf('Opera')<0){androIE=true;}
function byId(id){return document.getElementById(id);}
function ob(oname){if(document.getElementById)
return document.getElementById(oname);else if(document.all)
return document.all[name];}
function obv(oname){if(ob(oname))return ob(oname).value;else return'';}
function KeyEvent(e){if(window.event)
return window.event.keyCode;else
return e.which;}
function KeyCode(e){if(window.event)
return window.event.keyCode;else
return e.keyCode;}
function obAttValue(objname,attname){var obj=ob(objname);return objAttValue(obj,attname);}
function objAttValue(obj,attname){if(!obj){return'';}
else{att=obj.attributes.getNamedItem(attname);if(!att){return'';}
else{return att.value;}}}
function addEventListener(element,type,expression,uc){if(uc==null){uc=false;}
if(element.addEventListener){element.addEventListener(type,expression,uc);return true;}else if(element.attachEvent){element.attachEvent('on'+type,expression);return true;}else return false;}
function removeEventListener(el,eType,fn){if(el.removeEventListener){el.removeEventListener(eType,fn,false);}
else if(el.detachEvent){el.detachEvent('on'+eType,fn);}
else{return false;}}
function eventTarget(e){if(window.event)
return window.event.target;else
return e.currentTarget;}
function bodyKeyPress(e){keycode=KeyCode(e);if(keycode>=112&&keycode<=123){var objnum=keycode-111;var objname='object_for_f'+objnum.toString();obj=ob(objname);if(obj){obj.onclick();return false;}}
if(keycode==13){obj=ob('object_for_enter');if(obj){obj.onclick();return false;}}
if(typeof(x4letters)!='undefined'){var objId='object_for_'+x4letters[e.charCode-97];if(byId(objId))byId(objId).onclick();}
return true;}
function inputOnFocus(obj){if(obj.readOnly)return;obj.attributes.getNamedItem('x_value_focus').value=obj.value;focusColor(obj,true);}
function inputOnBlur(obj){if(obj.readOnly)return true;changeCheck(obj);focusColor(obj,false);return true;}
function inputClick(obj){changeCheck(obj);}
function inputOnKeyUp(e,obj){keycode=KeyCode(e);if((keycode>=113&&keycode<=121)||(keycode>=33&&keycode<=40)){bodyKeyPress(e);return false;}
if(obj.readOnly)return;if(keycode==38){}
else if(keycode==40){}
else{fieldformat(obj,keycode);changeCheck(obj);return true;}}
function fieldformat(obj,keycode){var type_id=objAttValue(obj,'x_type_id');var objval=obj.value;if(type_id=='ssn'){if(objval.length==3){if(keycode!=8){obj.value+='-';}}
if(objval.length==6){if(keycode!=8){obj.value+='-';}}}
if(type_id=='ph12'){if(objval.length==3){if(keycode!=8){obj.value+='-';}}
if(objval.length==7){if(keycode!=8){obj.value+='-';}}}
if(type_id=='date'){if(objval.length==6){if(objval.indexOf('/')==-1){if(objval.indexOf('-')==-1){var month=objval.substr(0,2);var day=objval.substr(2,2);var year=objval.substr(4,2);yearint=parseInt(year);if(yearint<30){year='20'+year}
else{year='19'+year}
obj.value=month+'/'+day+'/'+year;}}}}}
function changeCheck(obj){if(obj.readOnly)return;var x_value_original=objAttValue(obj,'x_value_original');var x_class_base=objAttValue(obj,'x_class_base');var x_mode=objAttValue(obj,'x_mode');if(x_mode!='ins'&&x_mode!='search'){if(obj.value!=x_value_original){obj.attributes.getNamedItem('x_class_base').value='ins';}
else{obj.attributes.getNamedItem('x_class_base').value='upd';}}
fieldColor(obj);}
function focusColor(obj,selected){if(obj.readOnly&&selected)return;if(selected){obj.attributes.getNamedItem('x_class_suffix').value='Selected';}
else{obj.attributes.getNamedItem('x_class_suffix').value='';}
fieldColor(obj);}
function fieldColor(obj){if(!obj.attributes.getNamedItem('x_class_base'))return;obj.className='x3'
+objAttValue(obj,'x_class_base')
+objAttValue(obj,'x_class_suffix')}
function fetchSELECT(table,input,col1,col1val,col2,col2val){var getString='?ajxfSELECT='+table
+'&pfx='+objAttValue(input,"nameprefix")
+'&col1='+col1
+'&col1val='+encodeURIComponent(col1val)
+'&col2='+col2
+'&col2val='+encodeURIComponent(col2val);andrax(getString);}
function FetchRow(table_id,pk_ob){andrax('?gp_fetchrow='+table_id+'&gp_pk='+encodeURI(ob(pk_ob).value))}
function adl_visible(){if(ajax_optionDiv){if(ajax_optionDiv.style.display!='none')return true;}
if(ajax_optionDiv_iframe){if(ajax_optionDiv_iframe.style.display!='none')return true;}}
function ajaxFetch(table_id_par,name_prefix,commapklist,commafklist,controls,columns,obj){afklist=commafklist.split(',');vallist='';for(x=0;x<afklist.length;x++){if(vallist.length!=0){vallist+=',';}
ctlname=name_prefix+afklist[x];if(ob(ctlname)){if(ob(ctlname).value==''){return}
else{vallist+=ob(ctlname).value}}}
href='?ajxFETCH=1'
+'&ajxtable='+table_id_par
+'&ajxcol='+commapklist
+'&ajxval='+vallist
+'&ajxcontrols='+controls
+'&ajxcolumns='+columns;andrax(href);}
function SetAndPost(varname,varvalue){ob(varname).value=varvalue;formSubmit();}
function SaveAndPost(varname,varvalue){ob('gp_save').value=1;ob(varname).value=varvalue;formSubmit();}
function formPostAjax(stringparms){var ajaxTMold=ajaxTM;ajaxTM=1;formPostString(stringparms);ajaxTM=ajaxTMold;}
function formPostString(stringparms){formSubmit(stringparms);}
function u3SS(stringparms){ob('u3string').value=stringparms;formSubmit();}
function SetAction(gp_var,gp_action,varname,varvalue){ob(gp_var).value=gp_action;SetAndPost(varname,varvalue);}
function drillDown(dd_page,ddc,ddv){drillDownSet(dd_page,ddc,ddv);formSubmit();}
function drillDownSet(dd_page,ddc,ddv){ob("dd_page").value=dd_page;ob("dd_ddc").value=ddc;ob("dd_ddv").value=ddv;}
function drillBack(gp_count){ob("dd_ddback").value=gp_count;formSubmit();}
function serverFunctionCall(name,parms){function_return_value=false;var str=''
str='?gp_function='+name;str+='&gp_parms='+parms;andrax(str);}
function Info2(table,field){var thevalue=ob(field).value;if(thevalue!=""){$href="?gp_page="+table+"&gp_pk="+thevalue;window.open($href);}
else{alert("Please select a value first!");}}
function doButton(e,compareto,obtoclick){var key=KeyEvent(e);if(key==compareto){if(ob(obtoclick)){ob(obtoclick).onclick();}}
return true;}
function Popup(url,caption){w=770;h=700;mytop=100;myleft=100;settings="width="+w+",height="+h+",top="+mytop+",left="+myleft+", "+"scrollbars=yes,resizable=yes";win=window.open(url,'Popup',settings);win.focus();}
function PopupSized(url,caption,w,h,mytop,myleft){settings="width="+w+",height="+h+",top="+mytop+",left="+myleft+", "+"status=no,scrollbars=no,resizable=no,toolbars=no";win=window.open(url,caption,settings);win.focus();}
function formSubmit(str){if(typeof(ajaxTM)=='undefined'){if(str){ob('Form1').action="index.php?"+str;}
ob("Form1").submit();return;}
if(ajaxTM==0){if(str){ob('Form1').action="index.php?"+str;}
ob("Form1").submit();return;}
formSubmitajxBUFFER(str);return;}
function formSubmitajxBUFFER(str){if(str){str='&'+str}else{str='';}
var postString='?ajxBUFFER=1'+str;var f=ob('Form1');for(var no=0;no<f.elements.length;no++){e=f.elements[no];if(e.type=='hidden'){if(e.value){postString+="&"+e.name+"=";postString+=encodeURIComponent(e.value);}}
else{if(e.attributes.getNamedItem('x_value_original')){xva=e.attributes.getNamedItem('x_value_original');if(e.tagName=='TEXTAREA'){postString+="&"+e.name+"=";postString+=encodeURIComponent(e.innerHTML);}
else{postString+="&"+e.name+"=";postString+=encodeURIComponent(e.value);}}
else{postString+="&"+e.name+"="+encodeURIComponent(e.value);}}}
andrax(postString);}
function fieldsReset(){var f=ob('Form1');var x='x'
for(var no=0;no<f.elements.length;no++){if(f.elements[no].type!='hidden'){x=f.elements[no];x.value=x.attributes.getNamedItem('x_value_original').value;if(x.attributes.getNamedItem('x_mode').value=='upd'){changeCheck(x);}}}}
function clearBoxes(){var f=ob('Form1');for(var no=0;no<f.elements.length;no++){obj=f.elements[no];if(obj.type=='hidden')continue;if(obj.type=='button')continue;if(obj.attributes.getNamedItem('x_no_clear').value=='Y')continue;f.elements[no].value='';}}
function ob(oname){if(document.getElementById)
return document.getElementById(oname);else if(document.all)
return document.all[name];}
function obv(oname){if(ob(oname))return ob(oname).value;else return'';}
function createRequestObject(){var ro;var browser=navigator.appName;if(browser=="Microsoft Internet Explorer"){ro=new ActiveXObject("Microsoft.XMLHTTP");}else{ro=new XMLHttpRequest();}
return ro;}
var http=createRequestObject();function AjaxCol(objname,objvalue){getString='?gp_ajaxcol=1';getString+='&gp_colname='+objname;getString+='&gp_colval='+objvalue;getString+='&gp_page='+ob('gp_page').value;if(objname!='appref'){getString+='&gp_appref='+ob('x2t_appref').value;}
andrax(getString);}
function AjaxWriteVal(ajxc1table,ajxcol,ajxval,ajxskey,list){getString='?ajxc1table='+ajxc1table;getString+='&ajxcol='+ajxcol;getString+='&ajxval='+encodeURI(ajxval);getString+='&ajxskey='+ajxskey;getString+='&ajxlist='+list
andrax(getString);}
function field_changed(page,field,value){getString='?gp_page='+page
+'&fwajax=field_changed'
+'&ajx_field='+field
+'&ajx_value='+encodeURIComponent(value)
andrax(getString);}
function andrax(getString,handler){http.open('get','index.php'+getString);if(handler==null){http.onreadystatechange=handleResponse;}
else{http.onreadystatechange=handler;}
http.send(null);}
function sndReq(action){getString="?gp_page="+ob('gp_page').value;getString+="&gp_skey="+ob('gp_skey').value;getString+=action;andrax(getString);}
function handleResponse(){if(http.readyState==4){var response=http.responseText;var elements=new Array();var controls=new Array();if(response.indexOf('|-|')!=-1){elements=response.split('|-|');for(x=0;x<elements.length;x++){controls=handleResponseOne(elements[x],controls);}}
else{controls=handleResponseOne(response,controls);}
if(controls){for(var x=0;x<controls.length;x++){changeCheck(ob(controls[x]));if(ob(controls[x]).onblur){ob(controls[x]).onblur();}}}}}
function handleResponseOne(one_element,controls){var update=new Array();if(one_element.indexOf('|')==-1){if(one_element.length==0)return;alert('Bad Ajax Response: '+one_element);}
else{update=one_element.split('|');if(update[0]=='echo'){alert(update[1]);}
else if(update[0]=='_focus'){ob(update[1]).focus();}
else if(update[0]=='_prompt'){prompt(update[1],update[2]);}
else if(update[0]=='_value'){if(ob(update[1])){ob(update[1]).value=update[2];controls.push(update[1]);}}
else if(update[0]=='_script'){eval(update[1]);}
else if(update[0]=='_redirect'){window.location=update[1];}
else if(update[0]=='_title'){document.title=update[1];}
else if(update[0]=='_alert'){if(ob('ql_right'))ob('ql_right').innerHTML=update[1];}
else{if(!ob(update[0])){alert('Bad Object: '+update[0]);alert('Value: '+update[1]);}
else{ob(update[0]).innerHTML=update[1];}}}
return controls;}
var aSelect=new Object();aSelect.divWidth=400;aSelect.divHeight=300;aSelect.div=false;aSelect.iframe=false;aSelect.row=false;function androSelect_onKeyUp(obj,strParms,e){var kc=e.keyCode;if(kc==9||kc==13){return true;}
if(kc==38||kc==40){if(!androSelect_visible())return;if(aSelect.div.firstChild.rows.length==0)return;if(!aSelect.row){var row=aSelect.div.firstChild.rows[0];var skey=objAttValue(row,'x_skey');androSelect_mo(row,skey);return;}
var row=byId('as'+aSelect.row);if(kc==38){var prev=objAttValue(row,'x_prev');if(prev!=''){var row=byId('as'+prev);androSelect_mo(row,prev);}}
if(kc==40){var next=objAttValue(row,'x_next');if(next!=''){var row=byId('as'+next);androSelect_mo(row,next);}}
return;}
if(typeof(obj.androSelect=='undefined')){obj.androSelect='';}
if(obj.androSelect==obj.value){return;}
if(!aSelect.div){aSelect.div=document.createElement('DIV');aSelect.div.style.display='none';aSelect.div.style.width=aSelect.divWidth+"px";aSelect.div.style.height=aSelect.divHeight+"px";aSelect.div.className='androSelect';aSelect.div.id='androSelect';document.body.appendChild(aSelect.div);var x=document.createElement('TABLE');aSelect.div.appendChild(x);}
if(aSelect.div.style.display=='none'){var postop=obj.offsetTop;var poslft=obj.offsetLeft;var objpar=obj;while((objpar=objpar.offsetParent)!=null){postop+=objpar.offsetTop;poslft+=objpar.offsetLeft;}
aSelect.div.style.top=(postop+obj.offsetHeight)+"px";aSelect.div.style.left=poslft+"px";aSelect.div.style.display='block';addEventListener(document,'click',androSelect_documentClick);}
aSelect.control=obj;aSelect.row=false;var url='?'+strParms+'&gpv=2&gp_letters='+obj.value.replace(" ","+");andrax(url,androSelect_handler);}
function androSelect_handler(){handleResponse();if(aSelect.div.firstChild){var table=aSelect.div.firstChild;if(table.rows.length>0){table.rows[0].onmouseover();}}}
function androSelect_onKeyDown(e){var kc=e.keyCode;if(kc==9||kc==13){if(!androSelect_visible())return true;removeEventListener(document,'click',androSelect_documentClick);if(aSelect.div.firstChild.rows.length==0){androSelect_hide();return true;}
if(aSelect.row){var row=byId('as'+aSelect.row);var pk=objAttValue(row,'x_value');aSelect.control.value=pk;}
androSelect_hide();return true;}}
function androSelect_hide(){aSelect.div.innerHTML=''
aSelect.div.style.display='none';}
function androSelect_visible(){if(aSelect.div==false)return false;if(aSelect.div.style.display=='none')return false;return true;}
function androSelect_documentClick(e){androSelect_hide();return false;}
function androSelect_mo(tr,skey){if(byId('as'+aSelect.row)){byId('as'+aSelect.row).className='';}
aSelect.row=skey;tr.className='hilite';}
function androSelect_click(value,suppress_focus){aSelect.control.value=value;androSelect_hide();if(suppress_focus==null){aSelect.control.focus();}}