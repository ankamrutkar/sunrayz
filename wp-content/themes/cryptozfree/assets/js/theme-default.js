jQuery(document).ready(function($) {


	// preloader
	
		setTimeout(function () {
			$('#preloader').fadeOut(500, function () { $(this).remove(); $(this).parents(".elementor-column-wrap").hide() });
		}, 500)
	
	  if (window.location.href.indexOf("home") > -1) {
		  $('body').addClass('home_not_set');
		}
		
	// Mobile Menu Toggle
			
		 $(".custom-toggle img").click(function() {
			$("#menu-main-menu-1").slideToggle();
			$("#menu-main-menu-1").toggleClass("check"); 
		  });
		  
	
		
		  $('#menu-main-menu-1 li.menu-item-has-children > a').on('click',function(event){
		
			event.preventDefault();
			$(this).toggleClass('selected');
			$(this).parent().find('ul').first().toggle(0);
			
			$(this).parent().siblings().find('ul').hide(0);
			
			//Hide menu when clicked outside
			$(this).parent().find('ul').parent().mouseleave(function(){ 
			  var thisUI = $(this);
			  $('html').click(function(){
				thisUI.children("ul.menu > ul.sub-menu").hide();
			thisUI.children("a").removeClass('selected');
					   
				$('html').unbind('click');
			  });
			});
			
			
		  });
		
	
	// If We add Shortcode for Display product & That Add Class method used for dividing Two Column Product  
	
	// 	Class add
		$('.trending li.product:nth-child(1)').addClass('product-list1');
		$('.trending li.product:nth-child(2)').addClass('product-list1');
		$('.trending li.product:nth-child(3)').addClass('product-list1');
		$('.trending li.product:nth-child(4)').addClass('product-list2');
		$('.trending li.product:nth-child(5)').addClass('product-list2');
		$('.trending li.product:nth-child(6)').addClass('product-list2');
	
	// 	id add	
		$('.divcart .elementor-widget-wrap').attr('id', 'mini-cart');
		$('.wmc-cart-wrapper').attr('id', 'wmc-cart');
	
	// 	div add	
		$( ".trending .product-list1" ).wrapAll( "<div id='product-list' />");
		$( ".trending .product-list2" ).wrapAll( "<div id='product-list' />");
		
	// 	Class add	
		$('.arrival li.product:nth-child(1)').addClass('product-list1');
		$('.arrival li.product:nth-child(2)').addClass('product-list1');
		$('.arrival li.product:nth-child(3)').addClass('product-list1');
		$('.arrival li.product:nth-child(4)').addClass('product-list2');
		$('.arrival li.product:nth-child(5)').addClass('product-list2');
		$('.arrival li.product:nth-child(6)').addClass('product-list2');
	// 	div add		
		$( ".arrival .product-list1" ).wrapAll( "<div id='product-list' />");
		$( ".arrival .product-list2" ).wrapAll( "<div id='product-list' />");
		
	
	
		
	
	// Apppend Cart in a New div
	
		var myEle = document.getElementById("wmc-cart");
		if(myEle){
		   
			function MoveDiv() {
			var fragment = document.createDocumentFragment();
			fragment.appendChild(document.getElementById('wmc-cart'));
			//Append id
			document.getElementById('mini-cart').appendChild(fragment);
		}
			MoveDiv();
		}
	// End cart
	
	
	});
	
	
	