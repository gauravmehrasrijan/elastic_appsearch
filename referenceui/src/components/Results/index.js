import React from "react";
import "./result.css";
export default class Result extends React.Component {
  render() {
      const content = this.props;
      console.log(content);
      return <h1>Hii</h1>;
//     return this.props.content.map((element) => {
//       return (
//         <div className="result-card">
//           <h2 className="result-heading">{element.title}</h2>
//           <p className="result-line">{element.url}</p>
//           <p className="result-line">{element.id}</p>
//         </div>
//       );
//     });
  }
}
