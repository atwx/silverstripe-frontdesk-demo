import '@fontsource-variable/open-sans'
import hljs from 'highlight.js/lib/core'
import php from 'highlight.js/lib/languages/php'
import yaml from 'highlight.js/lib/languages/yaml'
import xml from 'highlight.js/lib/languages/xml'
import javascript from 'highlight.js/lib/languages/javascript'
import css from 'highlight.js/lib/languages/css'

hljs.registerLanguage('php', php)
hljs.registerLanguage('yaml', yaml)
hljs.registerLanguage('html', xml)
hljs.registerLanguage('javascript', javascript)
hljs.registerLanguage('css', css)

// Code of the feature pages (features/<key>)
document.querySelectorAll('pre.demo-code code').forEach((block) => {
    hljs.highlightElement(block)
})
