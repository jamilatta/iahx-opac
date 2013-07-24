$(document).ready(function(){
    nav();
    filters();
});

function nav(){
    var speed = 300;
    var navHeight = $(".h-nav").height();

    $(".i-nav-show").on("click", function(){
        $(this).hide();
        $(".i-nav-hide").show();

        $(".h-nav").css({"height":"0px", "display":"block"}).stop().animate({"height":navHeight},speed);
        $('.c-results-list').hide();
    });

    $(".i-nav-hide").on("click", function(){
        $(this).hide();
        $(".i-nav-show").show();

        $(".h-nav").stop().animate({"height":"0px"}, speed, function(){
            $(this).hide();
        });
        $('.c-results-list').show();
    });
};

function filters(){
    var speed = 300;
    var iShow = "<span class='i-show'></span>";
    var iHide = "<span class='i-hide'></span>";

    $(".show-filters").on("click", function(){
        var element = $(this);
        $('.filters').toggle();
    });


    $(".c-filters-lia").on("click", function(){
        var element = $(this);
        var filtersHeight = element.siblings(".c-filters-sub").height();
        var click = element.find("span").attr("class");

        if(click == "i-show"){
            element.find(".i-show").addClass("i-hide").removeClass("i-show");
            element.siblings(".c-filters-sub").css({"height":"0px", "display":"block"}).stop().animate({"height":filtersHeight},speed);
        }else{
            element.find(".i-hide").addClass("i-show").removeClass("i-hide");
            element.siblings(".c-filters-sub").stop().animate({"height":"0px"}, speed, function(){
                $(this).hide();
                $(this).css({"height":"auto"});
            });
        }
    });

    $(".c-filters-ck").on("click", function(){
        var element = $(this);
        var pegarID = element.attr("id");
        var filters = $("#filters-add");
        var texto = element.siblings(".c-filters-lbl").text();

        if(element.is(":checked")){
            filters.append("<span id='"+ pegarID +"' class='c-filters-select'><span class='c-filters-remove'></span>"+ texto + "</span>");
        }else{
            $("span#"+pegarID).remove();
        }

        $(".c-filters-remove").on("click", function(){
            var ID = $(this).parent().attr("id");
            $("input#"+ID).attr("checked",false);
            $(this).parent().remove();
        });
    });
};
