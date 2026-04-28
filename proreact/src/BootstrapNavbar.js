import React, {useState, useEffect} from 'react'
import './Navbar.css'
import Nav from './Nav.js'


export default function BootstrapNavbar() {
  const [toggleMenu, setToggleMenu] = useState(false)
  const [screenWidth, setScreenWidth] = useState(window.innerWidth)


  const toggleNav = () => {
    setToggleMenu(!toggleMenu)
  }

  useEffect(() => {

    const changeWidth = () => {
      setScreenWidth(window.innerWidth);
    }

    window.addEventListener('resize', changeWidth)

    return () => {
        window.removeEventListener('resize', changeWidth)
    }

  }, [])
  
  
  const search3=()=>{
	
 window.open ("http://192.168.20.118/search3.php?x=4","","menubar=0,resizable=1,width=480,height=620");
	
};
  

  return ( 
    <nav>
      {(toggleMenu || screenWidth > 500) && (
      <ul className="list">
      <li className="items">Home</li>
      <li className="items">Services</li>
	   <li onClick={search3} className="items">Search</li>
      <li className="items">Contact</li>
    </ul>
      )}

      <button onClick={toggleNav} className="btn">BTN</button>
    </nav>
	
  )
}
