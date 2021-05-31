import React from "react";

class RenderDate extends React.Component {

  render() {
    let result = this.props.result;
    const type = result.type.raw;
    const month = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    let date = null;
    let formatted = '';
    let location = '';

    if(type === 'article' || type === 'blog_post'){
      if(result.hasOwnProperty('created')){
        date = new Date(parseInt(result.created.raw) * 1000);
      }
    }else if(type === 'event'){
      if(result.hasOwnProperty('field_event_date')){
        let event_date = result.field_event_date.raw.split(',');
        date = new Date(Date.parse(event_date[0]));
      }
    } 

    if(date){
      formatted = month[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
    }

    if(result.hasOwnProperty('field_is_event_online')){
      if(result.field_is_event_online.raw !== 'undefined' && !parseInt(result.field_is_event_online.raw)){
        location = result.field_event_location.raw
      }
    }
    
    return (
      <div>
        <span className="card__date">{ formatted }</span><p> {location}</p>
      </div>
    )
  }
}

class RenderImage extends React.Component {

  render() {
    let image = "";
    const result = this.props.result;
    
    if(result.hasOwnProperty('field_article_thumbnail')){
      if(result.field_article_thumbnail.raw !== ''){
        image = result.field_article_thumbnail.raw;
      }
    }

    if(result.hasOwnProperty('field_event_image')){
      if(result.field_event_image.raw !== ''){
        image = result.field_event_image.raw;
      }
    }

    if(result.hasOwnProperty('field_story_thumbnail')){
      if(result.field_story_thumbnail.raw !== ''){
        image = result.field_story_thumbnail.raw;
      }
    }

    if(image){
      return (
        <span className="blog-img"><img height="220px" width="220px" alt={image} src={image} /></span>
      )
    }

    return (null)
    
  }
}

class RenderTitle extends React.Component{
  render(){
    const result = this.props.result;
    let title = result.title.snippet.includes('<em>') ? result.title.snippet: result.title.raw;
    return (
      <span className="sui-result__title"
        dangerouslySetInnerHTML={{ __html: title }}
      />
    )
  }
}

class RenderDescription extends React.Component{
  render(){
    const result = this.props.result;
    let body = result.body_summary.raw !== '' ? result.body_summary.raw: result.body.raw;
    body = this.truncate(body, 500, true);
    return (
      <div className="sui-result__value"
        dangerouslySetInnerHTML={{ __html: body }}
      />
    )
  }

  truncate( str, n, useWordBoundary ) {

    if(str === 'undefined' || str === null){return ""; }

    str = str.trim();
    
    if (str.length <= n) { return str; }
    const subString = str.substr(0, n-1); // the original check
    return (useWordBoundary 
      ? subString.substr(0, subString.lastIndexOf(" ")) 
      : subString) + "&hellip;";
  };
}

class AddIcon extends React.Component{
  render(){
    let useTag = '';
    const result = this.props.result;
    if(result.hasOwnProperty('type')){
      if(result.type.raw === 'article'){
        useTag = '<use xlink:href="/themes/custom/particle/dist/app-drupal/assets/spritemap.svg?cacheBuster=#sprite-news"/>';
      }

      if(result.type.raw === 'event' && result.hasOwnProperty('field_is_event_online')){
        
        if(result.field_is_event_online.raw === "0"){
          useTag = '<use xlink:href="/themes/custom/particle/dist/app-drupal/assets/spritemap.svg?cacheBuster=#sprite-location-pin" />';
        }else{
          useTag = '<use xlink:href="/themes/custom/particle/dist/app-drupal/assets/spritemap.svg?cacheBuster=#sprite-online-meeting"/>';
        }
      }
    }
   

    return(
      <div className="svgicon-default">
        <svg viewBox="0 0 20 20" dangerouslySetInnerHTML={{__html: useTag }} />
      </div>
    )
  }
}

export default ({ result }) => (
  <li className={`sui-result searchDiv sui-${result.type.raw} ${ (result.hasOwnProperty('field_is_event_online') && result.field_is_event_online.raw) ? 'sui-event-online' : 'off'  }`
    
    }>
    <AddIcon result={result} />
    <div className="sui-result__header">
      <a href={result.path.raw}>
        <RenderTitle result={result} />
      </a>
    </div>

    <div className="sui-result__body">

      <ul className="sui-result__details">
        <li>
          <span className="sui-result__value" >
            <RenderImage result={result}/>
            <RenderDescription result={result} />
          </span>
          
        </li>
        <li>
            <RenderDate result={result}/>
        </li>
      </ul>
    </div>
  </li>
);
