(()=>{"use strict";var e={n:l=>{var t=l&&l.__esModule?()=>l.default:()=>l;return e.d(t,{a:t}),t},d:(l,t)=>{for(var a in t)e.o(t,a)&&!e.o(l,a)&&Object.defineProperty(l,a,{enumerable:!0,get:t[a]})},o:(e,l)=>Object.prototype.hasOwnProperty.call(e,l)};const l=window.wp.blocks,t=window.React,a=window.wp.serverSideRender;var n=e.n(a);const o=window.wp.blockEditor,r=window.wp.components,c=window.wp.element,i=window.wp.hooks,s=window.wp.data;function m(e,l=[],t=""){for(var a=0;a<e.length;a+=1)l.push({label:t+e[a].name,value:e[a].id}),e[a].children.length>0&&m(e[a].children,l,t.trim()+"—");return l}const p=JSON.parse('{"UU":"aiovg/videos"}');(0,l.registerBlockType)(p.UU,{attributes:function(){var e,l,t={};for(var a in aiovg_blocks.videos){var n=aiovg_blocks.videos[a].fields;for(var o in n)t[n[o].name]={type:(e=n[o].type,l=void 0,l="string","categories"==e?l="array":"number"==e?l="number":"checkbox"==e&&(l="boolean"),l),default:n[o].value}}return t}(),edit:function({attributes:e,setAttributes:l}){const a=aiovg_blocks.videos,p=(0,s.useSelect)((e=>{const l=e("core").getEntityRecords("taxonomy","aiovg_categories",{per_page:-1});let t=[];if(l&&l.length>0){let e=function(e){for(var l,t={},a=[],n=0;n<e.length;n+=1)t[e[n].id]=n,e[n].children=[];for(n=0;n<e.length;n+=1)0==(l=e[n]).parent?a.push(l):t.hasOwnProperty(l.parent)&&e[t[l.parent]].children.push(l);return a}(l),a=m(e);t=[...t,...a]}return t})),u=(0,s.useSelect)((e=>{const l=e("core").getEntityRecords("taxonomy","aiovg_tags",{per_page:-1});let t=[];if(l)for(var a=0;a<l.length;a+=1)t.push({label:l[a].name,value:l[a].id});return t})),g=e=>t=>{l({[e]:t})},b=(0,c.useRef)();return(0,c.useEffect)((()=>{b.current?(0,i.applyFilters)("aiovg_block_init",e):b.current=!0})),(0,t.createElement)(t.Fragment,null,(0,t.createElement)(o.InspectorControls,null,Object.keys(a).map(((n,c)=>{return s=n,(0,i.applyFilters)("aiovg_block_toggle_panels",!0,s,e)&&(0,t.createElement)(r.PanelBody,{key:"aiovg-block-panel-"+c,title:a[n].title,initialOpen:0==c,className:"aiovg-block-panel"},Object.keys(a[n].fields).map(((c,s)=>((a,n)=>{if(c=a.name,s=!0,"show_more"!=c&&"more_label"!=c&&"more_link"!=c||(s=!1),!(0,i.applyFilters)("aiovg_block_toggle_controls",s,c,e))return"";var c,s;const m=a.placeholder?a.placeholder:"",b=a.description?a.description:"";switch(a.type){case"header":return(0,t.createElement)(r.PanelRow,{key:n},(0,t.createElement)("h3",{className:"aiovg-no-margin"},a.label));case"categories":return(0,t.createElement)(r.PanelRow,{key:n,className:"aiovg-block-multiselect"},(0,t.createElement)(r.SelectControl,{multiple:!0,label:a.label,help:b,options:p,value:e[a.name],onChange:g(a.name)}));case"tags":return(0,t.createElement)(r.PanelRow,{key:n,className:"aiovg-block-multiselect"},(0,t.createElement)(r.SelectControl,{multiple:!0,label:a.label,help:b,options:u,value:e[a.name],onChange:g(a.name)}));case"number":return(0,t.createElement)(r.PanelRow,{key:n},(0,t.createElement)(r.RangeControl,{label:a.label,help:b,placeholder:m,value:e[a.name],min:a.min,max:a.max,onChange:g(a.name)}));case"textarea":return(0,t.createElement)(r.PanelRow,{key:n},(0,t.createElement)(r.TextareaControl,{label:a.label,help:b,placeholder:m,value:e[a.name],onChange:g(a.name)}));case"select":case"radio":let c=[];for(let e in a.options)c.push({label:a.options[e],value:e});return(0,t.createElement)(r.PanelRow,{key:n},(0,t.createElement)(r.SelectControl,{label:a.label,help:b,options:c,value:e[a.name],onChange:g(a.name)}));case"checkbox":return(0,t.createElement)(r.PanelRow,{key:n},(0,t.createElement)(r.ToggleControl,{label:a.label,help:b,checked:e[a.name],onChange:(d=a.name,e=>{l({[d]:e})})}));case"color":return(0,t.createElement)(r.PanelRow,{key:n},(0,t.createElement)(o.PanelColorSettings,{title:a.label,colorSettings:[{label:aiovg_blocks.i18n.select_color,value:e[a.name],onChange:g(a.name)}]}));default:return(0,t.createElement)(r.PanelRow,{key:n},(0,t.createElement)(r.TextControl,{label:a.label,help:b,placeholder:m,value:e[a.name],onChange:g(a.name)}))}var d})(a[n].fields[c],"aiovg-block-control-"+s))));var s}))),(0,t.createElement)("div",{...(0,o.useBlockProps)()},(0,t.createElement)(r.Disabled,null,(0,t.createElement)(n(),{block:"aiovg/videos",attributes:e}))))}})})();