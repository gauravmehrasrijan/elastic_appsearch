import React from "react";

class RenderDate extends React.Component {

  render() {
    let result = this.props.result;
    const type = result.type.raw;
    const month = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dev'];
    let date = null;
    let formatted = '';
    let location = '';

    if(type === 'article'){
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
      <span>
        <b>{ formatted }</b><p> {location}</p>
      </span>
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
        <span className="blog-img"><img src={image} /></span>
      )
    }

    return (null)
    
  }
}

export default ({ result }) => (
  <li className="sui-result">
    <div className="sui-result__header">
      <a href={result.path.raw}>
        <span
          className="sui-result__title"
          // Snippeted results contain search term highlights with html and are
          // 100% safe and santitized, so we dangerously set them here
          dangerouslySetInnerHTML={{ __html: result.title.snippet }}
        />
      </a>
    </div>

    <div className="sui-result__body">

      <ul className="sui-result__details">
        <li>
          <span className="sui-result__value" >
            <RenderImage result={result}/>
            { result.body.raw.substring(0, 500) }
          </span>
          
        </li>
        <li>
          <div className="sui-result__value">
            <RenderDate result={result}/>
          </div>
        </li>
      </ul>
      
    </div>

  </li>
);
