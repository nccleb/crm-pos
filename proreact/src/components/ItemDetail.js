import React,{useState,useEffect} from 'react';
import './App.css';




function ItemDetail({match}) {  

   useEffect( () =>{
	  fetchItem();
    console.log(match);
   },[]);
   
    const[item,setItem]= useState({

      images:{}
    });
	const fetchItem = async () =>{
		const fetchItem = await fetch(
		 //'https://fortnite-public-api.theapinetwork.com/store/get?ids=${match.params.id}'
     `https://reqres.in/api/users/${match.params.id}`
		);
		const item = await fetchItem.json();
		
		setItem(item.data);
    console.log(item.data);

	};
	
		
	
	
	
	
	
  return (  
    <div >  
	<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
   

	<h1>{item.first_name}</h1>
  <img src={item.avatar} alt=""/>
  
    </div>  
  );  
}  


export default ItemDetail;

