 import React,{useContext} from 'react';
 import {MovieContext} from './MovieContext';
 import './App.css';
 import {Link} from 'react-router-dom';
 //import  Home from './Home';

 const refreshPage = ()=>{
     window.location.reload();
   }


function Nav(){
	 
	const [movies,setMovies] = useContext(MovieContext);

    const navStyle={
		color:'white'
		
	};
    const search3=()=>{
	
		window.open ("http://192.168.20.118/test204.php?x=4","","menubar=0,resizable=1,width=480,height=620");
		   
	   };

	return(
	<nav>
	<h3>NBC</h3>
	
	<ul className="nav-links">

	<Link style={navStyle}  to="/">
	<li>Home</li>
     
	</Link>

    <Link style={navStyle} to="/AddMovie" >
	<li>Add</li>
     
	</Link>


	

    <Link style={navStyle} to="/MovieList" >
	<li>List</li>
     
	</Link>

	

    

	<Link style={navStyle} to="/AboutUs">
	<li>AboutUs</li>
	</Link>
	
	<Link style={navStyle} to="/Shop">
	<li>Shop</li>
	</Link>

	<Link style={navStyle}  >
	<li onClick={search3} >Search</li>
	</Link>
	</ul>
	<p>List of Movies :{movies.length}</p>
	</nav>
	
	
	);
	
	
}



export default Nav;	 
	  
	  
	  
	  
	  