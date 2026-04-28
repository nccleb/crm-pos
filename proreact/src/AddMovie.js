import React,{ useState,useContext } from 'react';
import { MovieContext } from './MovieContext';

const AddMovie= () =>{
    
const [name,SetName] = useState('');
const [price,SetPrice] = useState('');
const [movies,setMovies] = useContext(MovieContext);

const updateName = e =>{

  SetName(e.target.value);

}

const updatePrice = e =>{

    SetPrice(e.target.value);
  
  }

  const addMovie = e =>{
    e.preventDefault();
    setMovies(prevMovies=>[...prevMovies,{name:name,price:price}]);

  
  } 

  return(
    <div>
        <br/> <br/><br/> 
   <form onSubmit={addMovie}>
       <input type="text" name="name" value={name} onChange={updateName} />
       <input
       type="text"
       name="price"
       value={price}
       onChange={updatePrice}
       />
       <button>Submit</button>

    
    
    </form>
    </div>


  );

  };

  export default AddMovie;


  
