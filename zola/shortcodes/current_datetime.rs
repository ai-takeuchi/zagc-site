// shortcodes/current_datetime.rs
use chrono::prelude::*;
use tera::Context;
use tera::Result;

pub fn current_datetime(_: &Context, _: &HashMap<String, Value>) -> Result<Value> {
    let now: DateTime<Local> = Local::now();
    let formatted = now.format("%Y年%m月%d日 %H時%M分%S秒").to_string();
    Ok(formatted.into())
}