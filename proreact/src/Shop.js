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
	};
	
		
	
	
	
	
	
  return (  
    <div >  
	<h1>hello</h1>
	
	
    {items.map(item =>(
	<h1 key={item.id}>
	<Link to={`/Shop/${item.id}`}>{item.email}</Link>
	 </h1>
	))}
    </div>  
  );  
}  


export default Shop;


