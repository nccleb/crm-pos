 <?php
session_start();
 $_SESSION["name"];

 $_SESSION["password"];




?>
 

 
 

 
 <!DOCTYPE html>
<html>
<head>
  
<?php include('head.php'); ?>
  <link rel="stylesheet" href="css/stylei.css">
  <link rel="stylesheet" href="css/stylei2.css">
   
  <link rel="stylesheet" href="css/whatsappButton.css" />
  <script src="js/test371.js"></script>




		





</head>
<body>
  









<nav class="navbar navbar-inverse">
  <div class="container-fluid">
    <div class="navbar-header">
      
      
    </div>
   
  </div>
</nav>
 
 
 
 
 
 
 <div id="id01" class="container text-center">  

  
  <!-- Trigger the modal with a button -->
  <!--button type="button" class="btn btn-default btn-lg" id="myBtn">Login</button-->

  <!-- Modal -->
  <div class=""  role="">
    <div class="">
    
      <!-- Modal content-->
      <div class="">
        <div class="" style="padding:35px 50px;">
          <!--button type="" class="" data-dismiss="></button-->
          <!--h4><span class="glyphicon glyphicon-lock"></span> Login</h4-->
        </div>
        <div class="" style="padding:40px 50px;">
          <form role="form" method="post"  action="ajaxsubmit2.php">
            <div class="form-group">
              <label for="usrname"><span class=""></span> Username</label>
              <input type="text" name="name1"   class="form-control" id="name" placeholder="Enter username">
            </div>
            <div class="form-group">
              <label for="psw"><span class=""></span> Password</label>
              <input type="password" name="password1"   class="form-control" id="password" placeholder="Enter password">
            </div>
           
              <button type="submit" class="btn btn-success btn-block"><span class=""></span> Login</button>
          </form>
        </div>
        <div class="modal-footer">
          
          <span class="psw">Forgot <a href="reset_pass.php">password?</a></span>
          <!--p>Not a member? <a href="test205.php">Sign Up</a></p-->
          
        </div>
      </div>
      
    </div>
  </div> 
</div><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>






<?php include('footer.php'); ?>


</body>

</html>