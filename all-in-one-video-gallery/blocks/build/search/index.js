(()=>{"use strict";var e={n:o=>{var l=o&&o.__esModule?()=>o.default:()=>o;return e.d(l,{a:l}),l},d:(o,l)=>{for(var a in l)e.o(l,a)&&!e.o(o,a)&&Object.defineProperty(o,a,{enumerable:!0,get:l[a]})},o:(e,o)=>Object.prototype.hasOwnProperty.call(e,o)};const o=window.wp.blocks,l=window.wp.serverSideRender;var a=e.n(l);const n=window.wp.blockEditor,t=window.wp.components,r=window.ReactJSXRuntime,s=JSON.parse('{"UU":"aiovg/search"}');(0,o.registerBlockType)(s.UU,{edit:function({attributes:e,setAttributes:o}){const{template:l,keyword:s,category:c,tag:i,sort:g,search_button:d,target:b}=e;return(0,r.jsxs)(r.Fragment,{children:[(0,r.jsx)(n.InspectorControls,{children:(0,r.jsxs)(t.PanelBody,{title:aiovg_blocks.i18n.general_settings,children:[(0,r.jsx)(t.PanelRow,{children:(0,r.jsx)(t.SelectControl,{label:aiovg_blocks.i18n.select_template,value:l,options:[{label:aiovg_blocks.i18n.vertical,value:"vertical"},{label:aiovg_blocks.i18n.horizontal,value:"horizontal"}],onChange:e=>o({template:e})})}),(0,r.jsx)(t.PanelRow,{children:(0,r.jsx)(t.ToggleControl,{label:aiovg_blocks.i18n.search_by_keywords,checked:s,onChange:()=>o({keyword:!s})})}),(0,r.jsx)(t.PanelRow,{children:(0,r.jsx)(t.ToggleControl,{label:aiovg_blocks.i18n.search_by_categories,checked:c,onChange:()=>o({category:!c})})}),(0,r.jsx)(t.PanelRow,{children:(0,r.jsx)(t.ToggleControl,{label:aiovg_blocks.i18n.search_by_tags,checked:i,onChange:()=>o({tag:!i})})}),(0,r.jsx)(t.PanelRow,{children:(0,r.jsx)(t.ToggleControl,{label:aiovg_blocks.i18n.sort_by_dropdown,checked:g,onChange:()=>o({sort:!g})})}),(0,r.jsx)(t.PanelRow,{children:(0,r.jsx)(t.ToggleControl,{label:aiovg_blocks.i18n.search_button,checked:d,onChange:()=>o({search_button:!d})})}),(0,r.jsx)(t.PanelRow,{children:(0,r.jsx)(t.SelectControl,{label:aiovg_blocks.i18n.search_results_page,value:b,options:[{label:aiovg_blocks.i18n.default_page,value:"default"},{label:aiovg_blocks.i18n.current_page,value:"current"}],onChange:e=>o({target:e})})})]})}),(0,r.jsx)("div",{...(0,n.useBlockProps)(),children:(0,r.jsx)(t.Disabled,{children:(0,r.jsx)(a(),{block:"aiovg/search",attributes:e})})})]})}})})();