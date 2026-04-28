import React,{useState,useEffect} from 'react';
import './App.css';
import {Link} from 'react-router-dom';



function Shop() {  


	


   useEffect( () =>{
	  fetchItems();
   },[]);
   
    const[items,setItems]= useState([]);
	const fetchItems = async () =>{
		const data = await fetch(
		 'https://reqres.in/api/users?page=2'
		);
		const items = await data.json();
		console.log(items.data);
		setItems(items.data);
		//let Arr = JSON.stringify(items.data);
		//localStorage.setItem('is-Open', Arr);

      /*
		let item= items.data.map(function(el){
			
			return Object.keys(el)[0];;
			
		});
		console.log(item);
       
       */

		
	};
       
	
	
	
	var email = localStorage.getItem("is-Op");
var d = new Date();
d.setTime(d.getTime() + (1*24*60*60*1000));
var expires = "expires="+ d.toUTCString();
document.cookie = 'email' + "=" + email+ ";" + expires + ";path=/";

	

	
  return (  
    <div >  
	<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
	<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
	
	
	{items.map(item =>(
	<h1 key={item.id}>
	{item.id==10? localStorage.setItem("is-Op", item.first_name):""}
    

	 </h1>

	))}
    
    { localStorage.getItem("is-Op")}

	
    </div>  
  );  
}  


export default Shop;


