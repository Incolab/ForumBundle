/* 
 * MIT Licence @ David Salbei
 */

var DocMTMType = function (name, nametype, container_id) {
    this.role_fields = [];
    this.name = name;
    this.nametype = nametype;
    this.container_id = container_id;
    this.container = document.getElementById(this.container_id);
    this.data_prototype = this.container.getAttribute("data-prototype");
    this.nb_items = 0;
};

DocMTMType.prototype.init = function () {
    
    this.container.appendChild(this.gen_btn());
};

/**
 * @param html HTML representing a single element
 * @return element
 */
DocMTMType.prototype.htmlToElement = function (html) {
    var template = document.createElement('template');
    template.innerHTML = html;
    return template.content.firstChild;
};

DocMTMType.prototype.gen_btn = function (container_id) {
    var container = document.getElementById(container_id);
    
    var li = document.createElement("li");
    var a = document.createElement("a");
    a.setAttribute("href", "#");
    a.setAttribute("id", "add-mtm-"+this.container_id);
    a.innerHTML = "Add "+this.nametype;
    
    a.setAttribute("onclick", this.name + ".onclick_insert_mtm(event ,'nom' ,'" + this.data_prototype + "')");
    
    /*
    a.addEventListener( "click", function (e) {
        e.preventDefault();
        this.name + ".insert_mtm_element(" + this.data_prototype + ")";
    });
    */
    li.appendChild(a);
    return li;
};

DocMTMType.prototype.onclick_insert_mtm = function (evt, name, row) {
    evt.preventDefault();
    this.insert_mtm_element(name, row);
};

DocMTMType.prototype.onclick_delete_mtm = function (evt, elmt) {
    evt.preventDefault();
    elmt.parentNode.parentNode.removeChild(elmt.parentNode);
};

DocMTMType.prototype.insert_mtm_element = function (name, row, err) {
    
    var li = document.createElement("li");
    li.setAttribute("class", "role");
    
    if (typeof err !== "undefined") {
        var span = document.createElement("span");
        span.innerHTML = err;
        li.appendChild(span);
    }
    
    row = row.replace(/__name__/g, this.nb_items);
    this.nb_items++;
    var rowobject = this.htmlToElement(row);
    console.log(row);
    li.appendChild(rowobject);
    
    var btn = document.createElement("button");
    btn.setAttribute("class", "tiny button");
    //btn.setAttribute("id", "mtm-btn-"+name);
    btn.innerHTML = "Delete "+name;
    
    btn.setAttribute("onclick", this.name + ".onclick_delete_mtm(event, this)");
    /*
    btn.addEventListener("click", function(e) {
        e.preventDefault();
        
    });
    */
    li.appendChild(btn);
    this.container.insertBefore(li, this.container.childNodes[0]);
};

DocMTMType.prototype.find_role_field = function (field_id) {
    
};