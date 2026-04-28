import Navbar from "./Navbar";
import AboutUs from './AboutUs';
import AboutUs2 from './AboutUs2';
import ItemDetail from './ItemDetail';
import Shop from './Shop';
import {BrowserRouter as Router , Switch ,Route } from 'react-router-dom';





const App = () => {
  return (
    
    <Router>
    <header>
      <div className="nav-area">
        <a href="#" className="logo">
         Ncc
        </a>
        <Navbar />
        <Switch>
	
	
	<Route path="/AboutUs" exact component={AboutUs} />
	<Route path="/AboutUs2" exact component={AboutUs2} />
  <Route path="/Shop/" exact component={Shop} />
  <Route path="/Shop/:id" component={ItemDetail} />
  
	</Switch>
      </div>
    </header>
    </Router>
   
  );
};

export default App;

