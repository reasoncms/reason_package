// custom scripts for luther.edu
$(document).ready(function(){$("body").addClass("js");$(".fullPost p, .pageContent p, .blurb p, .announcement p").each(function(){var e=$(this);e.html(e.html().replace(/&nbsp;/g,""))});$("#tabs .fragment-1").addClass("active");$("#tabs #fragment-1").addClass("active");$("#mobile-nav a.search").click(function(e){$(this).toggleClass("open");$("#search-nav").toggleClass("open");e.preventDefault()});$("#navWrap a.toggle").click(function(e){$(this).toggleClass("open");$("#navWrap #minisiteNavigation").slideToggle("400");$("#navWrap .subNavElements").slideToggle("400");e.preventDefault()});$("li.navListItem.accordion > a").click(function(e){var t=$(this).parent();if(t.hasClass("closed")){t.removeClass("closed");t.addClass("open")}else{t.removeClass("open");t.addClass("closed")}t.children("ul").slideToggle("400");e.preventDefault()});$("li.navListItem.accordion.open ul.navList").css("display","block");$("#pageContent table").addClass("responsive");$("table.tablesorter").wrap('<div class="tableSorterWrap"></div>');$("a[class^=cta-]").each(function(e){(label=$(this).attr("class").match(/^cta\-([A-Za-z0-9_\-]+)/)[1])!="button"&&$(this).attr("onclick","_gaq.push(['trackEvent', 'call-to-action', 'click', '"+$(location).attr("pathname")+"button"+label+"']);")})});