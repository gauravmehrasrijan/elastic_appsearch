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
          <span
            className="sui-result__value"
            dangerouslySetInnerHTML={{
              __html: result.body.raw.substring(0, 500)
            }}
          />
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
