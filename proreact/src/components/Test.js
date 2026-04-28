import React from 'react';
import MenuItems from "./MenuItems";

function Test({ita,ite}) {
  
  const search3=()=>{
	
		window.open ("http://192.168.20.118/login20.html?x=4","","menubar=0,resizable=1,width=480,height=620");
		   
	   };
    
  return ( 
    
    



    
    <div >  
     {ite==1 ?  <a href="/Aboutus">{ita}</a>: ""}
	   {ite==2 ?  <a href="/Aboutus2">{ita}</a>: ""}
     {ite==3 ?  <a href="/shop">{ita}</a>: ""}
    
     {ite==5 ?  <a href="/Aboutus5">{ita}</a>: ""}
	   {ite==6 ?  <a href="/Aboutus6">{ita}</a>: ""}
     {ite==7 ?  <a href="/Aboutus7">{ita}</a>: ""}
     {ite==8 ?  <a href="/Aboutus8">{ita}</a>: ""}
     {ite==9 ?  <a onClick={search3}      href="#">{ita}</a>: ""}
	 
    </div>  
  );  
}
  





export default Test;