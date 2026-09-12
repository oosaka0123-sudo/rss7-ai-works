(function(){
"use strict";
var header=document.querySelector("header");
var btn=document.querySelector(".menu-btn");
var menu=document.getElementById("mobileMenu");
if(!header||!btn||!menu)return;
function setMenuOffset(){menu.style.top=header.offsetHeight+"px";}
function closeMenu(){menu.classList.remove("open");btn.setAttribute("aria-expanded","false");btn.setAttribute("aria-label","メニューを開く");}
function openMenu(){menu.classList.add("open");btn.setAttribute("aria-expanded","true");btn.setAttribute("aria-label","メニューを閉じる");}
setMenuOffset();
btn.addEventListener("click",function(){if(menu.classList.contains("open")){closeMenu();}else{openMenu();}});
menu.querySelectorAll("a").forEach(function(a){a.addEventListener("click",closeMenu);});
document.addEventListener("keydown",function(e){if(e.key==="Escape"&&menu.classList.contains("open")){closeMenu();btn.focus();}});
window.addEventListener("resize",function(){setMenuOffset();if(window.innerWidth>768&&menu.classList.contains("open")){closeMenu();}});
})();
