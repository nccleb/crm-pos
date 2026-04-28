import React ,{useState,useEffect,useRef} from 'react';
import { renderIntoDocument } from 'react-dom/test-utils';


function AboutUs() {  
  const [name,setName] = useState('')
  //const renderCount = useRef(1)
 // const inputRef = useRef()
 /*<div> I  rendered {renderCount.current}</div>

  useEffect(() => {
    renderCount.current = renderCount.current + 1
  })
 


  function focus(){

    inputRef.current.focus()

  }

*/
const prevName =useRef('')

useEffect(() => {
  prevName.current = name
} , [name])







  return (  
   
	
     
    
   
       <div>

         <br/> <br/> <br/><br/><br/> <br/> <br/><br/>
        
         
      <input  value= {name} onChange = {e=>setName(e.target.value)} />
      <div>my name is : {name} and it used to be {prevName.current}</div>
      
      </div>
      
    
  )
};


export default AboutUs;


    
  





