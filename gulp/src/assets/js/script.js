//= jquery.js

$(document).ready(function() {
	$(window).scroll(function() {
		checkHeaderPos();
	});
	$(window).on('resize', function(){
		checkHeaderPos();
	});

	function checkHeaderPos() {
		var height = $(window).scrollTop();
		if(height  > 0) {
			$('header').addClass('header_bg');
			$('.header_wrapper').addClass('header_wrapper-70');
			$('.welcome_box').addClass('welcome_box-adapt');
		}else{
			$('header').removeClass('header_bg');
			$('.header_wrapper').removeClass('header_wrapper-70');
			$('.welcome_box').removeClass('welcome_box-adapt');
		}

		var win = $(window).width();
		if(win > 767) {
			$('.header_childMenu').removeAttr('style');
		}
		if (win <= 1023) {
			$('.welcome_box').addClass('welcome_box-adapt');
		} else{
			$('.welcome_box').removeClass('welcome_box-adapt');
		}
		if (win <= 767) {
			$('.header_parentMenu').css({'display' : 'none'})
		} else{
			$('.header_parentMenu').css({'display' : 'block', 'opacity' : '1'})
		}
	}
	checkHeaderPos();


	$('.filter_item').click(function(event) {
		var target = event.target;
		var parent = findParent(target);
		if($(parent).hasClass('active')){
			return false;
		}
		removeFilterClasses();
		$(parent).addClass('active');
		if($(parent).hasClass('filter-all')) {
			$('.publications').addClass('filtered-all');
		}else if($(parent).hasClass('filter-grc')) {
			$('.publications').addClass('filtered-grc');
		}else if($(parent).hasClass('filter-wpf')) {
			$('.publications').addClass('filtered-wpf');
		}
	});

	function removeFilterClasses() {
		$('.filter_item').removeClass('active');
		$('.publications').removeClass('filtered-wpf');
		$('.publications').removeClass('filtered-grc');
		$('.publications').removeClass('filtered-all');
	}

	function findParent(target) {
		if($(target).hasClass('filter_item') == true){
			return target;
		} else if (target == document){
			return false;
		} else {
			target = target.parentNode;
			return findParent(target);
		}
	}
	$('.bar').click(function(e) {
		e.stopPropagation();
		$('.header_parentMenu').slideToggle();
		$('.header_childMenu').slideUp();
		angleReset();
	});
	$(window).click(function(e) {
		var target = e.target;
		var win = $(window).width();
		if (win <= 767 && !$(target).hasClass('menu_angle')) {
			$('.header_parentMenu').slideUp();
			$('.header_childMenu').slideUp();
			angleReset();
		}
	});
	var angle = document.createElement('div');
	angle.className = 'menu_angle';
	var angleSrc = $('#templateUri').val() + 'assets/img/icons/custom/angle-down.svg';
	$(angle).css({backgroundImage: 'url(' + angleSrc + ')'});
	$(angle).click(function() {
		var parent = $(this).parent()[0];
		$(this).toggleClass('active');
		$(parent).children('.header_childMenu').slideToggle();
	});

	function angleReset() {
		$('.menu_angle').removeClass('active');
	}

	$('.header_parentLi.has_childs').append(angle);

	var currentLink = $('#currentLink').val();

	$('.share_item-twitter').click(function() {
		window.open("https://twitter.com/intent/tweet?url=" + currentLink, "pop", "width=600, height=400, scrollbars=no");
	});
	$('.share_item-facebook').click(function() {
		window.open("https://www.facebook.com/sharer/sharer.php?u=" + currentLink, "pop", "width=600, height=400, scrollbars=no");
	});
	$('.share_item-google').click(function() {
		window.open("https://plus.google.com/share?url=" + currentLink, "pop", "width=600, height=400, scrollbars=no");
	});


    function isScrolledIntoView($elem, $window) {
		scroll_pos  = $(window).scrollTop() + $(window).height();
	    element_pos = $elem.offset().top;
	    return (scroll_pos > element_pos);
    }


	var $window   = $(window);
	var animElems = [$('.animate1'), $('.animate2'), $('.animate3'), $('.animate4'), $('.animate5')];
	$(document).on("scroll", function () {
		checkScrollAnimate();
	});
	function checkScrollAnimate() {
		animElems.forEach(function(item, index) {
			if(item.length != 0) {
			    if (isScrolledIntoView(item, $window)) {
			        item.addClass("active");
			        animElems = animElems.filter(function(animElem) {return animElem !== item});
			    }
			}
		});
	}
	checkScrollAnimate();

});
function footerSubscribe() {
	var form = document.querySelector("#mc-embedded-subscribe-form");
	form.submit();
}