import React,{useState} from 'react';
import '../node_modules/bootstrap/dist/css/bootstrap.min.css';
import BootstrapNavbar from './BootstrapNavbar';
import Nav from './Nav';
import './App.css';
import {BrowserRouter as Router , Switch ,Route } from 'react-router-dom';
import AboutUs from './AboutUs';
import Shop from './Shop';
import ItemDetail from './ItemDetail';
import Home from './Home';
import {MovieProvider} from './MovieContext';
import AddMovie from './AddMovie';
import MovieList from './MovieList';

function App() {  


    
  return ( 
	
	<MovieProvider> 
    <Router>
	<div className="App">
		
	<Nav />
	<Switch>
	
	<Route path="/" exact component={Home} />
	<Route path="/AddMovie" exact component={AddMovie} />
	<Route path="/MovieList" exact component={MovieList} />
	<Route path="/AboutUs" component={AboutUs} />
	<Route path="/Shop" exact component={Shop} />
	<Route path="/Shop/:id" component={ItemDetail} />

	</Switch>
	</div>
	</Router>
	</MovieProvider>
	
	
)
	
	
}  


	

	







export default App;	 
	  
	  
	  
	  
	  
	 
		  
		  
		  
		 
			  
			  
			  
			  
			  
			  
			
			  
			
		
		  
       
	  
	  
	  
           
	
	  
	  
	 
		  
		  
          
       
       
  

   
 

 




    
