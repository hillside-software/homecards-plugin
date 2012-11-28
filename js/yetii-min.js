/*Yetii - Yet (E)Another Tab Interface Implementation,version 1.7,http://www.kminek.pl/lab/yetii/,Copyright (c) Grzegorz Wojcik,Code licensed under the BSD License: http://www.kminek.pl/bsdlicense.txt*/
function Yetii(){this.defaults={id:null,active:1,interval:null,wait:null,persist:null,tabclass:"tab",activeclass:"active",callback:null,leavecallback:null};this.activebackup=null;for(var c in arguments[0]){this.defaults[c]=arguments[0][c]}this.getTabs=function(){var d=[];var f=document.getElementById(this.defaults.id).getElementsByTagName("*");var g=new RegExp("(^|\\s)"+this.defaults.tabclass.replace(/\-/g,"\\-")+"(\\s|$)");for(var e=0;e<f.length;e++){if(g.test(f[e].className)){d.push(f[e])}}return d};this.links=document.getElementById(this.defaults.id+"-nav").getElementsByTagName("a");this.listitems=document.getElementById(this.defaults.id+"-nav").getElementsByTagName("li");this.show=function(e){for(var d=0;d<this.tabs.length;d++){this.tabs[d].style.display=((d+1)==e)?"block":"none";if((d+1)==e){this.addClass(this.links[d],this.defaults.activeclass);this.addClass(this.listitems[d],this.defaults.activeclass+"li")}else{this.removeClass(this.links[d],this.defaults.activeclass);this.removeClass(this.listitems[d],this.defaults.activeclass+"li")}}if(this.defaults.leavecallback&&(e!=this.activebackup)){this.defaults.leavecallback(this.defaults.active)}this.activebackup=e;this.defaults.active=e;if(this.defaults.callback){this.defaults.callback(e)}};this.rotate=function(e){this.show(this.defaults.active);this.defaults.active++;if(this.defaults.active>this.tabs.length){this.defaults.active=1}var d=this;if(this.defaults.wait){clearTimeout(this.timer2)}this.timer1=setTimeout(function(){d.rotate(e)},e*1000)};this.next=function(){var d=(this.defaults.active+1>this.tabs.length)?1:this.defaults.active+1;this.show(d);this.defaults.active=d};this.previous=function(){var d=((this.defaults.active-1)==0)?this.tabs.length:this.defaults.active-1;this.show(d);this.defaults.active=d};this.previous=function(){this.defaults.active--;if(!this.defaults.active){this.defaults.active=this.tabs.length}this.show(this.defaults.active)};this.gup=function(e){e=e.replace(/[\[]/,"\\[").replace(/[\]]/,"\\]");var d="[\\?&]"+e+"=([^&#]*)";var g=new RegExp(d);var f=g.exec(window.location.href);if(f==null){return null}else{return f[1]}};this.parseurl=function(f){var d=this.gup(f);if(d==null){return null}if(parseInt(d)){return parseInt(d)}if(document.getElementById(d)){for(var e=0;e<this.tabs.length;e++){if(this.tabs[e].id==d){return(e+1)}}}return null};this.createCookie=function(f,g,h){if(h){var e=new Date();e.setTime(e.getTime()+(h*24*60*60*1000));var d="; expires="+e.toGMTString()}else{var d=""}document.cookie=f+"="+g+d+"; path=/"};this.readCookie=function(e){var g=e+"=";var d=document.cookie.split(";");for(var f=0;f<d.length;f++){var h=d[f];while(h.charAt(0)==" "){h=h.substring(1,h.length)}if(h.indexOf(g)==0){return h.substring(g.length,h.length)}}return null};this.hasClass=function(g,f){var e=g.className.split(" ");for(var d=0;d<e.length;d++){if(e[d]==f){return true}}return false};this.addClass=function(e,d){if(!this.hasClass(e,d)){e.className=(e.className+" "+d).replace(/\s{2,}/g," ").replace(/^\s+|\s+$/g,"")}};this.removeClass=function(e,d){e.className=e.className.replace(new RegExp("(^|\\s)"+d+"(?:\\s|$)"),"$1");e.className.replace(/\s{2,}/g," ").replace(/^\s+|\s+$/g,"")};this.tabs=this.getTabs();this.defaults.active=(this.parseurl(this.defaults.id))?this.parseurl(this.defaults.id):this.defaults.active;if(this.defaults.persist&&this.readCookie(this.defaults.id)){this.defaults.active=this.readCookie(this.defaults.id)}this.activebackup=this.defaults.active;this.show(this.defaults.active);var a=this;for(var b=0;b<this.links.length;b++){this.links[b].customindex=b+1;this.links[b].onclick=function(){if(a.timer1){clearTimeout(a.timer1)}if(a.timer2){clearTimeout(a.timer2)}a.show(this.customindex);if(a.defaults.persist){a.createCookie(a.defaults.id,this.customindex,0)}if(a.defaults.wait){a.timer2=setTimeout(function(){a.rotate(a.defaults.interval)},a.defaults.wait*1000)}return false}}if(this.defaults.interval){this.rotate(this.defaults.interval)}};
