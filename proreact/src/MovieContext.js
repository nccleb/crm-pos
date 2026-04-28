import React,{useState,createContext} from 'react';
export const MovieContext = createContext();
export const MovieProvider = props =>{
    const[movies,setMovies] = useState([
        {
            name : 'Harry Potter',
            price : '$10',
            id : 23124
        },
        {
            name : 'Darry Potter',
            price : '$11',
            id : 23125
        },
        {
            name : 'Carry Potter',
            price : '$2',
            id : 23126
        },
        
         {
            name : 'Sarry Potter',
            price : '$20',
            id : 23127
        }


    ]);

   return(
      <MovieContext.Provider value = {[movies,setMovies]} >
        {props.children}
      </MovieContext.Provider>



   );



}