<?php  ?> 
{name:'Perex separator (more)', openWith:'\n<!--more-->\n', className:'more'},

{separator:'---------------' },
{name:'<?php _e('Heading 1') ?>', key:'1', closeWith:function(markItUp) { return miu.texyTitle(markItUp, '#') }, placeHolder:'Your title here...', className:'h1'},
{name:'Heading 2', key:'2', closeWith:function(markItUp) { return miu.texyTitle(markItUp, '*') }, placeHolder:'Your title here...', className:'h2'},
{name:'Heading 3', key:'3', closeWith:function(markItUp) { return miu.texyTitle(markItUp, '=') }, placeHolder:'Your title here...', className:'h3'},
{name:'Heading 4', key:'4', closeWith:function(markItUp) { return miu.texyTitle(markItUp, '-') }, placeHolder:'Your title here...', className:'h4'},
{name:'Thematic break (HR)', openWith:'\n\n--------------------------------\n\n', className:'hr'},

{separator:'---------------' },
{name:'Bold', key:'B', closeWith:'**', openWith:'**', className:'bold', placeHolder:'Your text here...'}, 
{name:'Italic', key:'I', closeWith:'*', openWith:'*', className:'italic', placeHolder:'Your text here...'}, 

{separator:'---------------' },
{name:'Bulleted list', className:'list-bullet', replaceWith:function(h){return bullet_list(h)}}, 
{name:'Numeric list', className:'list-numeric', replaceWith:function(h){return bullet_list(h)}}, 

{separator:'---------------' },
// {name:'Picture', key:'P', openWith:'[* ', closeWith:' (!(.([![Alt text]!]))!) *]', placeHolder:'[![Url:!:http://]!]', className:'image'}, 
{name:'Link', key:'H', openWith:'"', closeWith:'":[![Url:!:http://]!]', placeHolder:'Your text to link...', className:'link' },

{separator:'---------------' },
{name:'Quote (block)', key:'Q', openWith:'> ', className:'blockquote'},
{name:'Quote (inline)', openWith:'>>', closeWith:'<<', placeHolder:'Short quotation', className:'quote'},
{name:'Cite', openWith:'~~',  closeWith:'~~', placeHolder:'Author/Source', className:'cite'}, 

{separator:'---------------' },
{separator:'---------------' },

{name:'Experimental', className:'extra', dropMenu: [
// {name:'Product parts', className:'extra', dropMenu: [
// 	{name:'Product-menu', className:'div', openWith:'\n/---div .[product-menu]\n.[reset]\n"<span>Sekce 1</span>":#', closeWith:'\n\\---', placeHolder:''},
// 	{separator:'---------------' },
// 	]
// },
// {name:'Code (block)', className:'blockcode', openWith:'\n/---code [![Language]!]\n', closeWith:'\n\\---\n', placeHolder:'code'},
// {name:'Code (inline)', className:'code', openWith:'`', closeWith:'`', placeHolder:'code'},

{separator:'---------------' },

{name:'Div', className:'div', openWith:'\n/---div .[[![Classes]!]]\n', closeWith:'\n\\---', placeHolder:''},

{name:'Texy switch off', className:'off', closeWith:'\'\'', openWith:'\'\'', placeHolder:'Texy is not applied here'},	

{separator:'---------------' },

{ name:'Serif', className:'font', beforeInsert:function(){
	jQuery('#content').css('font-family','Constantia, serif')
}},
{ name:'Sans-serif', className:'font', beforeInsert:function(){
	jQuery('#content').css('font-family','Calibri, sans-serif')
}},
{ name:'Monospaced', className:'font', beforeInsert:function(){
	jQuery('#content').css('font-family','monospace')
}}
]
},
{name:'Texy quick help', beforeInsert:function(){popUp ('<?php echo $Texy_pluginPath ?>texy-help.html')}, className:'help'},

<?php 