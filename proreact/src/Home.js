import React from 'react';


		 const refreshPage = ()=>{
     window.location.reload();
   }
	
 

  
function Home() {  
    if(!window.location.hash) {
		window.location = window.location + '#loaded';
		window.location.reload();
	}
  
  return (  
    <div > 
      <br/> <br/>
    <h1>Home</h1>
    <h1>{Math.random()}</h1>
  
      <button onClick={refreshPage}>Refresh</button>
    </div>  
  );  
 

}




export default Home;




