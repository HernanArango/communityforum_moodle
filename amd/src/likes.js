
define(['jquery'], function($) {
		return{
        
	        like : function() {
				$('.forumpost').on('click','.like',function(){
						postid = $(this).attr('postid');
						userid = $(this).attr('userid');
						like = $(this).attr('like');


						if (like == 1) {
							//poner como deseleccionado al like
							$(this).removeClass('like-selected');
			    			$(this).addClass('like-unselected');

			    			$(this).attr('like',0);

			    			like = 0;
			    			
						}
						else{
							//poner como seleccionado al like
							$(this).removeClass('like-unselected');
			    			$(this).addClass('like-selected');

			    			$(this).attr('like',1);
			    			like = 1;
						}

						

		    			//deseleccionar el dislike si esta seleccionado
		    			$('#dislike'+postid).removeClass('like-selected');
		    			$('#dislike'+postid).addClass('like-unselected');
		    			$('#dislike'+postid).attr('like',0);

						
						$.post( "likes.php",{"postid":postid, "userid": userid, "like":like}, function( data ) {
		    		    	
							
			            });
			    });
			            

			           
	        },

	        dislike : function() {
				$('.forumpost').on('click','.dislike',function(){
						postid = $(this).attr('postid');
						userid = $(this).attr('userid');
						like = $(this).attr('like');

						
						if (like == 1) {
							//poner como deseleccionado al like
							$(this).removeClass('like-selected');
			    			$(this).addClass('like-unselected');

			    			$(this).attr('like',0);

			    			like = 0
			    			
						}
						else{
							//poner como seleccionado al like
							$(this).removeClass('like-unselected');
			    			$(this).addClass('like-selected');

			    			$(this).attr('like',1);

			    			like = -1;
						}

						

		    			//deseleccionar el dislike si esta seleccionado
		    			$('#like'+postid).removeClass('like-selected');
		    			$('#like'+postid).addClass('like-unselected');
		    			$('#like'+postid).attr('like',0);

						
						$.post( "likes.php",{"postid":postid, "userid": userid, "like":like}, function( data ) {
		    		    	
		              		console.log(data)
		            	});
	            });
	        }
	    }

});