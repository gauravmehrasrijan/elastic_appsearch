import React from "react";
import ReactDOM from "react-dom";
import App from "./App";

// let window[appConfig] =  JSON.parse('{"engineName":"localcontent","endpointBase":"https:\/\/enterprise-search-deployment-0e1d40.ent.eastus2.azure.elastic-cloud.com","searchKey":"search-7jz7dxpk18rdygc32owidk77","resultFields":["body","changed","created","field_article_type","field_blog_section","field_blog_topic","field_tax_article_type","field_tax_content_format","field_tax_disease","field_tax_event_type","field_tax_ga_type","field_tax_research_type","field_tax_topic","path","status","title","type"],"sortFields":["field_tax_topic","type"],"facets":["field_article_type","field_tax_topic","type","field_tax_research_type"],"titleField":"title","urlField":"path"}');


ReactDOM.render( < App / > , document.getElementById("root"));
